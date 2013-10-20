<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

spl_autoload_register(function($className) {
			if (file_exists('./classes/' . $className . '.php')) {
				require_once './classes/' . $className . '.php';
			}
		});



$pdo = new PDO('mysql:dbname=test_nestedtree;host=127.0.0.1', 'root', '', array(
	array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"'),
));

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


class Kategoria extends NestedSets {

}

$oTree = new Kategoria('tree', $pdo);
$nodes = $oTree->getTree();
$view = new View(__DIR__ . '/views/NestedSetsTest.php');
$view->set('nodes', $nodes);
echo $view;

$oTree = new Kategoria('tree', $pdo);
$nodes = $oTree->getChildren(2);
$view = new View(__DIR__ . '/views/NestedSetsTest.php');
$view->set('nodes', $nodes);
echo $view;

var_dump($nodes);
