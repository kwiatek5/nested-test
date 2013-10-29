<?php

/*

  http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/
  http://www.sideralis.org/baobab/
  https://github.com/etrepat/baum

 */

abstract class NestedSets {

	private $pdo;
	private $treeName;

	public function __construct($treeName, PDO $pdo) {
		$this->setTreeName($treeName);
		$this->setPDO($pdo);
	}

	final private function setTreeName($treeName) {
		$this->treeName = $treeName;
	}

	final public function getTreeName() {
		return $this->treeName;
	}

	private function setPDO($pdo) {
		$this->pdo = $pdo;
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	}

	final private function _insert(array $treeNode) {

		$fields = array_map(function($el) {
			return '`' . $el . '`';
		}, array_keys($treeNode));

		$values = array_values($treeNode);
		$valuesQuestionMarks = implode(', ', array_fill(0, count($values), '?'));

		$query = $this->pdo->prepare('INSERT INTO `' . $this->getTreeName() . '` (' . implode(', ', $fields) . ') VALUES (' . $valuesQuestionMarks . ')');
		$query->execute($values);

		return $this->pdo->lastInsertId();
	}

	final private function _lockTable() {
		$query = $this->pdo->prepare('LOCK TABLES `' . $this->getTreeName() . '` WRITE');
		$query->execute();
	}

	final private function _unlockTables() {
		$query = $this->pdo->prepare('UNLOCK TABLES');
		$query->execute();
	}

	final public function getRoot() {
		$query = $this->pdo->prepare('SELECT * FROM `' . $this->getTreeName() . '` WHERE lft = ? LIMIT 1');
		$query->execute(array(1));
		$node = $query->fetch();
		return $node ? $node : false;
	}

	final public function getNode($id) {
		$sql = 'SELECT node.*, (SELECT COUNT(parent.id) - 1 
								FROM `' . $this->getTreeName() . '` AS parent
								WHERE node.lft >= parent.lft 
								AND node.lft <= parent.rgt
								) AS depth
				FROM ' . $this->getTreeName() . ' AS node
				WHERE id = ?
				LIMIT 1';

		$query = $this->pdo->prepare($sql);
		$query->execute(array($id));
		$node = $query->fetch();
		return $node ? $node : false;
	}

	final public function getTree($id = false) {
		$where = '';
		$params = array();

		if ($id !== false) {
			$node = $this->getNode($id);
			if (!$node) {
				return array();
			}

			$where .= 'WHERE node.lft >= ? AND node.lft <= ?';
			$params = array_merge($params, array($node['lft'], $node['rgt']));
		}

		$sql = 'SELECT node.*, (SELECT COUNT(parent.id) - 1
								FROM `' . $this->getTreeName() . '` AS parent
								WHERE node.lft >= parent.lft 
								AND node.lft <= parent.rgt
								) AS depth
				FROM ' . $this->getTreeName() . ' AS node ' . $where . ' ORDER BY node.lft';

		$query = $this->pdo->prepare($sql);
		$query->execute($params);
		$tree = $query->fetchAll();

		return $tree;
	}

	final public function getSubTree($id) {
		return $this->getTree((int) $id);
	}

	final public function getTreeSize($id = false) {
		$where = '';
		$params = array();

		if ($id !== false) {
			$node = $this->getNode($id);
			if (!$node) {
				return 0;
			}

			$where .= 'WHERE node.lft >= ? AND node.lft <= ?';
			$params = array_merge($params, array($node['lft'], $node['rgt']));
		}

		$sql = 'SELECT COUNT(node.id) as `size`
				FROM ' . $this->getTreeName() . ' AS node ' . $where . ' LIMIT 1';

		$query = $this->pdo->prepare($sql);
		$query->execute($params);
		$size = $query->fetch();

		return $size ? $size['size'] : 0;
	}

	final public function getSubTreeSize($id) {
		return $this->getTreeSize((int) $id);
	}

	final public function getTreeHeight() {
		$sql = 'SELECT MAX(t.depth) as depth
				FROM (
					SELECT node.id, (SELECT COUNT(parent.id) - 0
									FROM `' . $this->getTreeName() . '` AS parent
									WHERE node.lft >= parent.lft 
									AND node.lft <= parent.rgt
									) AS depth
					FROM ' . $this->getTreeName() . ' AS node) as t
				LIMIT 1';

		$query = $this->pdo->prepare($sql);
		$query->execute();
		$height = $query->fetch();

		return $height ? $height['depth'] : 0;
	}

	final public function getTreeWithoutNode($id) {
		$where = '';
		$params = array();

		$node = $this->getNode($id);
		if (!$node) {
			return array();
		}

		$where .= 'WHERE node.lft < ? OR node.lft > ?';
		$params = array_merge($params, array($node['lft'], $node['rgt']));

		$sql = 'SELECT node.*, (SELECT COUNT(parent.id) - 1 
								FROM `' . $this->getTreeName() . '` AS parent
								WHERE node.lft >= parent.lft 
								AND node.lft <= parent.rgt
								) AS depth
				FROM `' . $this->getTreeName() . '` AS node ' . $where . ' ORDER BY node.lft';

		$query = $this->pdo->prepare($sql);
		$query->execute($params);
		$tree = $query->fetchAll();

		return $tree;
	}

	public function getAncestors($id) { // przodkowie
		$sql = 'SELECT parent.*, (SELECT COUNT(node.id) - 1 
								FROM `' . $this->getTreeName() . '` AS node
								WHERE parent.lft >= node.lft 
								AND parent.lft <= node.rgt
								) AS depth
				FROM `' . $this->getTreeName() . '` AS node, `' . $this->getTreeName() . '` AS parent
				WHERE node.lft >= parent.lft
				AND node.lft <= parent.rgt
				AND node.id = ?
				ORDER BY parent.lft';

		$query = $this->pdo->prepare($sql);
		$query->execute(array($id));
		$nodes = $query->fetchAll();

		return $nodes;
	}

	final public function getLeafs() {
		$sql = 'SELECT node.*, (SELECT COUNT(parent.id) - 1 
								FROM `' . $this->getTreeName() . '` AS parent
								WHERE node.lft >= parent.lft 
								AND node.lft <= parent.rgt
								) AS depth
				FROM ' . $this->getTreeName() . ' AS node 
				WHERE node.lft + 1 = node.rgt
				ORDER BY node.lft';

		$query = $this->pdo->prepare($sql);
		$query->execute();
		$nodes = $query->fetchAll();

		return $nodes;
	}

	final public function getParent($id) {
		$sql = 'SELECT parent.*, (SELECT COUNT(node.id) - 1 
								FROM `' . $this->getTreeName() . '` AS node
								WHERE parent.lft >= node.lft 
								AND parent.lft <= node.rgt
								) AS depth
				FROM `' . $this->getTreeName() . '` AS node, `' . $this->getTreeName() . '` AS parent
				WHERE node.lft > parent.lft
				AND node.lft < parent.rgt
				AND node.id = ?
				ORDER BY parent.lft DESC
				LIMIT 1';

		$query = $this->pdo->prepare($sql);
		$query->execute(array($id));
		$node = $query->fetch();

		return $node ? $node : false;
	}

	final public function getChildren($id) {
		$sql = 'SELECT node.*, (COUNT(parent.name) - (sub_tree.depth + 1)) AS _depth
				FROM `' . $this->getTreeName() . '` AS node,
					`' . $this->getTreeName() . '` AS parent,
					`' . $this->getTreeName() . '` AS sub_parent,
					(
						SELECT node.name, (COUNT(parent.name) - 1) AS depth
						FROM `' . $this->getTreeName() . '` AS node,
						`' . $this->getTreeName() . '` AS parent
						WHERE node.lft BETWEEN parent.lft AND parent.rgt
						AND node.id = ?
						GROUP BY node.name
						ORDER BY node.lft
					) AS sub_tree
				WHERE node.lft BETWEEN parent.lft AND parent.rgt
				AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt
				AND sub_parent.name = sub_tree.name
				GROUP BY node.name
				HAVING _depth = 1
				ORDER BY node.lft';

		$query = $this->pdo->prepare($sql);
		$query->execute(array($id));
		$nodes = $query->fetchAll();

		return $nodes;
	}

	final public function getFirstChild($id) {
		$sql = 'SELECT node.*, (COUNT(parent.name) - (sub_tree.depth + 1)) AS _depth
				FROM `' . $this->getTreeName() . '` AS node,
					`' . $this->getTreeName() . '` AS parent,
					`' . $this->getTreeName() . '` AS sub_parent,
					(
						SELECT node.name, (COUNT(parent.name) - 1) AS depth
						FROM `' . $this->getTreeName() . '` AS node,
						`' . $this->getTreeName() . '` AS parent
						WHERE node.lft BETWEEN parent.lft AND parent.rgt
						AND node.id = ?
						GROUP BY node.name
						ORDER BY node.lft
					) AS sub_tree
				WHERE node.lft BETWEEN parent.lft AND parent.rgt
				AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt
				AND sub_parent.name = sub_tree.name
				GROUP BY node.name
				HAVING _depth = 1
				ORDER BY node.lft
				LIMIT 1';

		$query = $this->pdo->prepare($sql);
		$query->execute(array($id));
		$node = $query->fetch();

		return $node ? $node : false;
	}

	final public function getLastChild($id) {
		$sql = 'SELECT node.*, (COUNT(parent.name) - (sub_tree.depth + 1)) AS _depth
				FROM `' . $this->getTreeName() . '` AS node,
					`' . $this->getTreeName() . '` AS parent,
					`' . $this->getTreeName() . '` AS sub_parent,
					(
						SELECT node.name, (COUNT(parent.name) - 1) AS depth
						FROM `' . $this->getTreeName() . '` AS node,
						`' . $this->getTreeName() . '` AS parent
						WHERE node.lft BETWEEN parent.lft AND parent.rgt
						AND node.id = ?
						GROUP BY node.name
						ORDER BY node.lft
					) AS sub_tree
				WHERE node.lft BETWEEN parent.lft AND parent.rgt
				AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt
				AND sub_parent.name = sub_tree.name
				GROUP BY node.name
				HAVING _depth = 1
				ORDER BY node.lft DESC
				LIMIT 1';

		$query = $this->pdo->prepare($sql);
		$query->execute(array($id));
		$node = $query->fetch();

		return $node ? $node : false;
	}

	final public function getChildAtIndex($id, $index) {
		$index = ((int) $index) - 1;
		$nodes = $this->getChildren($id);
		if ($nodes && isset($nodes[$index])) {
			return $nodes[$index];
		}

		return false;
	}

	final public function addRoot(array $treeNode) {
		$root = $this->getRoot();
		if ($root) {
			return false;
		}

		unset($treeNode['id']);
		$treeNode['lft'] = 1;
		$treeNode['rgt'] = 2;

		$this->_lockTable();
		$id = $this->_insert($treeNode);
		$this->_unlockTables();

		return $id;
	}

	final public function insertAfter($id, array $treeNode) {

		$node = $this->getNode($id);
		if (!$node) {
			return false;
		}

		if ($node['lft'] == 1) { // root
			return false;
		}

		$rgt = $node['rgt'];

		unset($treeNode['id']);
		$treeNode['lft'] = $rgt + 1;
		$treeNode['rgt'] = $rgt + 2;

		$this->_lockTable();

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET lft = lft + 2 WHERE lft > ?');
		$query->execute(array($rgt));

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt + 2 WHERE rgt > ?');
		$query->execute(array($rgt));

		$id = $this->_insert($treeNode);

		$this->_unlockTables();

		return $id;
	}

	final public function insertBefore($id, array $treeNode) {

		$node = $this->getNode($id);
		if (!$node) {
			return false;
		}

		if ($node['lft'] == 1) { // root
			return false;
		}

		$lft = $node['lft'];

		unset($treeNode['id']);
		$treeNode['lft'] = $lft;
		$treeNode['rgt'] = $lft + 1;

		$this->_lockTable();

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET lft = lft + 2 WHERE lft >= ?');
		$query->execute(array($lft));

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt + 2 WHERE rgt >= ?');
		$query->execute(array($lft));

		$id = $this->_insert($treeNode);

		$this->_unlockTables();

		return $id;
	}

	final public function appendTo($id, array $treeNode) {

		$node = $this->getNode($id);
		if (!$node) {
			return false;
		}

		$rgt = $node['rgt'];

		unset($treeNode['id']);
		$treeNode['lft'] = $rgt;
		$treeNode['rgt'] = $rgt + 1;

		$this->_lockTable();

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET lft = lft + 2 WHERE lft >= ?');
		$query->execute(array($rgt));

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt + 2 WHERE rgt >= ?');
		$query->execute(array($rgt));

		$id = $this->_insert($treeNode);

		$this->_unlockTables();

		return $id;
	}

	final public function prependTo($id, array $treeNode) {

		$node = $this->getNode($id);
		if (!$node) {
			return false;
		}

		$lft = $node['lft'];

		unset($treeNode['id']);
		$treeNode['lft'] = $lft + 1;
		$treeNode['rgt'] = $lft + 2;

		$this->_lockTable();

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET lft = lft + 2 WHERE lft > ?');
		$query->execute(array($lft));

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt + 2 WHERE rgt > ?');
		$query->execute(array($lft));


		$id = $this->_insert($treeNode);

		$this->_unlockTables();

		return $id;
	}

	final public function deleteNode($id) {
		$node = $this->getNode($id);

		if (!$node) {
			return false;
		}

		$lft = $node['lft'];
		$rgt = $node['rgt'];
		$size = $rgt - $lft + 1;

		$this->_lockTable();

		$query = $this->pdo->prepare('DELETE FROM `' . $this->getTreeName() . '` WHERE lft >= ? AND rgt <= ?');
		$query->execute(array($lft, $rgt));

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET lft = lft - ' . $size . ' WHERE lft > ?');
		$query->execute(array($rgt));

		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt - ' . $size . ' WHERE rgt > ?');
		$query->execute(array($rgt));

		$this->_unlockTables();

		return true;
	}

	final public function insertChildAtIndex($id, $index, array $treeNode) {
		$nodes = $this->getChildren($id);
		if (!$nodes) {
			return false;
		}

		$countChildren = count($nodes);
		if (!$countChildren) {
			return $this->appendTo($id, $treeNode);
		}

		$index = (int) $index;
		if ($index < 1) {
			$index = 1;
		} elseif ($index > $countChildren) {
			$index = $countChildren;
		}

		$node = $nodes[$index - 1];

		return $this->insertBefore($node['id'], $treeNode);
	}

	final public function moveLeft($id) {
		$parent = $this->getParent($id);
		if (!$parent) {
			return false;
		}

		$node = $this->getNode($id); // node exists, beacuse has parent
		$nodes = $this->getChildren($parent['id']);
		if ($nodes[0]['id'] == $node['id']) { // is first or the only
			return false;
		}

		$index = false;
		for ($i = 1, $c = count($nodes); $i < $c; $i++) {
			if ($node['id'] == $nodes[$i]['id']) {
				$index = $i;
				break;
			}
		}

		$size = $node['rgt'] - $node['lft'] + 1;

		$prevNode = $nodes[$index - 1]; // exists, bacause $index always exists
		$prevSize = $prevNode['rgt'] - $prevNode['lft'] + 1;

		$this->_lockTable();

		// make hole for prev node after node
		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET lft = lft + ' . $prevSize . ' WHERE lft > ?');
		$query->execute(array($node['rgt']));
		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt + ' . $prevSize . ' WHERE rgt > ?');
		$query->execute(array($node['rgt']));

		// locate prev node after node
		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET lft = lft + ' . ($size + $prevSize) . ' WHERE lft >= ? AND lft < ?');
		$query->execute(array($prevNode['lft'], $prevNode['rgt']));
		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt + ' . ($size + $prevSize) . ' WHERE rgt > ? AND rgt <= ?');
		$query->execute(array($prevNode['lft'], $prevNode['rgt']));

//		// close gap before node
		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET lft = lft - ' . $prevSize . ' WHERE lft >= ?');
		$query->execute(array($node['lft']));
		$query = $this->pdo->prepare('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt - ' . $prevSize . ' WHERE rgt >= ?');
		$query->execute(array($node['lft']));

		$this->_unlockTables();

		return true;
	}

	final public function moveRight($id) {

	}

	final public function moveBefore($id, $idSibling) {
		
	}

	final public function moveAfter($id, $idSibling) {
		
	}

	final public function moveTo($id, $idParent) {
		
	}

}
