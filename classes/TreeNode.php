<?php

abstract class TreeNode {
	protected $id;
	protected $left;
	protected $right;
	protected $title;
	
	public function getId() {
		return $this->id;
	}
	
	public function getLeft() {
		return $this->left;
	}
	
	public function getRight() {
		return $this->right;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function setId($id) {
		$this->id = (int) $id;
		return $this;
	}
	
	public function setLeft($left) {
		$this->left = (int) $left;
		return $this;
	}
	
	public function setRight($right) {
		$this->right = (int) $right;
		return $this;
	}
	
	public function setTitle($title) {
		$this->title = (string) $title;
		return $this;
	}
	
	
	
	
}
