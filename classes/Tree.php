<?php

class Tree {

	private function _getSql($where) {
		$sql = 'SELECT node.*, (SELECT count(parent.id)-1 
								FROM tree AS parent
								WHERE node.lft >= parent.lft 
								AND node.lft <= parent.rgt
								) AS depth
				FROM tree AS node ' . $where . ' ORDER BY node.lft';

		return $sql;
	}

	public function getNode($id) {
		$sql = 'SELECT * FROM tree WHERE id = ?';
		$node = DB::q($sql, array($id));
		return ($node) ? $node[0] : false;
	}

	public function getParent() {
		$sql = 'SELECT * FROM tree WHERE lft = 1';
		$node = DB::q($sql);
		return ($node) ? $node[0] : false;
	}

	/**
	 * 
	 * @param false|int $id - if not false, then retreive subtree
	 * @return array
	 */
	public function getTree($id = false) {
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

		return DB::q($this->_getSql($where), $params);
	}

	/**
	 * Zwraca drzewo bez gałęzi (id) i jej dzieci
	 * @param int $id
	 * @return array
	 */
	public function getTreeExclude($id) {
		$where = '';
		$params = array();

		$node = $this->getNode($id);
		if (!$node) {
			return array();
		}

		$where .= 'WHERE node.lft < ? OR node.lft > ?';
		$params = array_merge($params, array($node['lft'], $node['rgt']));

		return DB::q($this->_getSql($where), $params);
	}

	public function addParent(array $treeNode) {
		$parentNode = $this->getParent();
		if ($parentNode) {
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

	public function addAfter(array $treeNode, $id) {

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

		DB::x('LOCK TABLES tree WRITE');
		DB::x('UPDATE tree SET lft = lft + 2 WHERE lft > ?', array($rgt));
		DB::x('UPDATE tree SET rgt = rgt + 2 WHERE rgt > ?', array($rgt));
		$id = $this->_insert($treeNode);
		DB::x('UNLOCK TABLES');

		return $id;
	}

	public function addBefore(array $treeNode, $id) {

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

		DB::x('LOCK TABLES tree WRITE');
		DB::x('UPDATE tree SET lft = lft + 2 WHERE lft >= ?', array($lft));
		DB::x('UPDATE tree SET rgt = rgt + 2 WHERE rgt >= ?', array($lft));
		$id = $this->_insert($treeNode);
		DB::x('UNLOCK TABLES');

		return $id;
	}

	public function addLastChild(array $treeNode, $id) {

		$node = $this->getNode($id);
		if (!$node) {
			return false;
		}

		$rgt = $node['rgt'];

		unset($treeNode['id']);
		$treeNode['lft'] = $rgt;
		$treeNode['rgt'] = $rgt + 1;

		DB::x('LOCK TABLES tree WRITE');
		DB::x('UPDATE tree SET lft = lft + 2 WHERE lft >= ?', array($rgt));
		DB::x('UPDATE tree SET rgt = rgt + 2 WHERE rgt >= ?', array($rgt));
		$id = $this->_insert($treeNode);
		DB::x('UNLOCK TABLES');

		return $id;
	}

	public function addFirstChild(array $treeNode, $id) {

		$node = $this->getNode($id);
		if (!$node) {
			return false;
		}

		$lft = $node['lft'];

		unset($treeNode['id']);
		$treeNode['lft'] = $lft + 1;
		$treeNode['rgt'] = $lft + 2;

		DB::x('LOCK TABLES tree WRITE');
		DB::x('UPDATE tree SET lft = lft + 2 WHERE lft > ?', array($lft));
		DB::x('UPDATE tree SET rgt = rgt + 2 WHERE rgt > ?', array($lft));
		$id = $this->_insert($treeNode);
		DB::x('UNLOCK TABLES');

		return $id;
	}

	private function _insert(array $treeNode) {

		$fields = array_map(function($el) {
					return '`' . $el . '`';
				}, array_keys($treeNode));

		$values = array_values($treeNode);
		$valuesQuestionMarks = implode(', ', array_fill(0, count($values), '?'));

		DB::x('INSERT INTO `tree` (' . implode(', ', $fields) . ') VALUES (' . $valuesQuestionMarks . ')', $values);
		return DB::lastID();
	}

	public function getAncestors($id) { // przodkowie
		$sql = 'SELECT parent.*
				FROM tree AS node, tree AS parent
				WHERE node.lft >= parent.lft
				AND node.lft <= parent.rgt
				AND node.id = ?
				ORDER BY parent.lft';
		
		return DB::q($sql, array($id));
	}

	public function moveAfter($id, $idAfter) {
		$node = $this->getNode($id);
		if (!$node) {
			return false;
		}
		$nodeAfter = $this->getNode($idAfter);
		if (!$nodeAfter) {
			return false;
		}

		$x = $this->getTreeExclude($id);

		echo '<pre>';
		var_dump($node, $nodeAfter, $x);
		echo '</pre>';
//		exit;
	}

	public function repair() {
		// zatkaj dziury

		$arr = array();

		$tree = $this->getTree();
		foreach ($tree as $k => $v) {
			$tree[$k] = array(
				'id' => $v['id'],
				'lft' => $v['lft'],
				'rgt' => $v['rgt'],
			);

			$arr[] = $v['lft'];
			$arr[] = $v['rgt'];

			sort($arr, SORT_NUMERIC);
			foreach ($arr as $a) {
				
			}
		}
	}

}
