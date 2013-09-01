<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

spl_autoload_register(function($className) {
			if (file_exists('./classes/' . $className . '.php')) {
				require_once './classes/' . $className . '.php';
			}
		});

$tree = new Tree();
$ancestors = $tree->getAncestors(10);
echo '<pre>';var_dump($ancestors);echo '</pre>';exit;
//$tree->addAfter(array('title' => 'Media Player'), 5);
//$tree->repair();
$tree->moveAfter(5, 7);
$nodes = $tree->getTree();
$view = new View(__DIR__ . '/views/index.php');
$view->set('nodes', $nodes);
echo $view;

$view = new View(__DIR__ . '/views/index.php');
$view->set('nodes', $tree->getTreeExclude(5));
echo $view;
