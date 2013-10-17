<?php
/*

http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/

2. Node getParent(Node) 
3. NodeCollection getSiblings(Node) 
4. NodeCollection getChildren(Node) 
5. Node|bool getPrevious(Node) 
6. Node|bool getNext(Node) 

9. bool appendTo(Node) 
9a. bool prependTo(Node) 
10. array asTree() 


class Node { 
    protected id 
    protected lft 
    protected rgt 

    1. function getRoot() { 
        select from tree where lft=1 
        return $node or false 
    } 




    10. function asTree() { 
        select * 
        from tree 
        where lft >= $this->lft 
        and rgt <= $this->rgt 
        order by lft 
    } 
}
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

	final public function getRoot() {
		$query = $this->pdo->prepare('SELECT * FROM `' . $this->getTreeName() . '` WHERE lft = ? LIMIT 1');
		$query->execute(array(1));
		$node = $query->fetch();
		return $node ? $node : false;
	}

	final public function getNode($id) {
		$query = $this->pdo->prepare('SELECT * FROM `' . $this->getTreeName() . '` WHERE id = ? LIMIT 1');
		$query->execute(array(1));
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
				FROM '. $this->getTreeName() . ' AS node ' . $where . ' ORDER BY node.lft';

		$query = $this->pdo->prepare($sql);
		$query->execute($params);
		$tree = $query->fetchAll();

		return $tree;
	}

	final public function getSubTree($id) {
		return $this->getTree((int) $id);
	}

	public function getTreeWithoutNode($id) {
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
		$sql = 'SELECT parent.*
				FROM `' . $this->getTreeName() . '` AS node, `' . $this->getTreeName() . '` AS parent
				WHERE node.lft >= parent.lft
				AND node.lft <= parent.rgt
				AND node.id = ?
				ORDER BY parent.lft';
				
		$query = $this->pdo->prepare($sql);
		$query->execute($id);
		$nodes = $query->fetchAll();
		
		return $nodes;
	}
	
	
	public function addRoot(array $treeNode) {
		$root = $this->getRoot();
		if ($root) {
			return false;
		}

		unset($treeNode['id']);
		$treeNode['lft'] = 1;
		$treeNode['rgt'] = 2;

		DB::x('LOCK TABLES tree WRITE');
		$id = $this->_insert($treeNode);
		DB::x('UNLOCK TABLES');

		return $id;
	}

	public function insertAfter($id, array $treeNode) {

		$node = $this->getNode($id);
		if (!$node) {
			return false;
		}

		if ($node['lft'] == 1) { // parent
			return false;
		}

		$rgt = $node['rgt'];

		unset($treeNode['id']);
		$treeNode['lft'] = $rgt + 1;
		$treeNode['rgt'] = $rgt + 2;

		DB::x('LOCK TABLES `' . $this->getTreeName() . '` WRITE');
		DB::x('UPDATE `' . $this->getTreeName() . '` SET lft = lft + 2 WHERE lft > ?', array($rgt));
		DB::x('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt + 2 WHERE rgt > ?', array($rgt));
		$id = $this->_insert($treeNode);
		DB::x('UNLOCK TABLES');

		return $id;
	}

	public function insertBefore($id, array $treeNode) {

		$node = $this->getNode($id);
		if (!$node) {
			return false;
		}

		if ($node['lft'] == 1) { // parent
			return false;
		}

		$lft = $node['lft'];

		unset($treeNode['id']);
		$treeNode['lft'] = $lft;
		$treeNode['rgt'] = $lft + 1;

		DB::x('LOCK TABLES `' . $this->getTreeName() . '` WRITE');
		DB::x('UPDATE `' . $this->getTreeName() . '` SET lft = lft + 2 WHERE lft >= ?', array($lft));
		DB::x('UPDATE `' . $this->getTreeName() . '` SET rgt = rgt + 2 WHERE rgt >= ?', array($lft));
		$id = $this->_insert($treeNode);
		DB::x('UNLOCK TABLES');

		return $id;
	}

}



































