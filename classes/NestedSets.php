<?php
/*
2. Node getParent(Node) 
3. NodeCollection getSiblings(Node) 
4. NodeCollection getChildren(Node) 
5. Node|bool getPrevious(Node) 
6. Node|bool getNext(Node) 
7. bool insertBefore(Node) 
8. bool insertAfter(Node) 
9. bool appendTo(Node) 
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
	protected $pdo;
	private $treeName;

	public function __construct($treeName, PDO $pdo) {
		$this->setTreeName($treeName);
		$this->setPDO($pdo);
	}
	
	private function setTreeName($treeName) {
		$this->treeName = $treeName;
	}
	
	public function getTreeName() {
		return $this->treeName;
	}
	
	private function setPDO($pdo) {
		$this->pdo = $pdo;
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	}

	final public function getRoot() {
		$query = $this->pdo->pdo->prepare('SELECT * FROM '. $this->treeName .' WHERE lft = ? LIMIT 1');
		$query->execute(array(1));
		$node = $query->fetch();
		return $node ? $node : false;
	}
}



































