<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

setlocale(LC_ALL, 'pl_PL.utf-8');

spl_autoload_register(function($className) {
			if (file_exists('./classes/' . $className . '.php')) {
				require_once './classes/' . $className . '.php';
			}
		});

$pdo = new PDO('mysql:dbname=test_nestedtree;host=127.0.0.1', 'root', '', array(
	array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'),
		));

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

class Kategoria extends NestedSets {
	
}

$oTree = new Kategoria('tree', $pdo);

//$pdo->exec('DELETE FROM tree');
$pdo->exec('TRUNCATE TABLE tree');

if (!$oTree->getRoot()) {
	$id_sprzet = $oTree->addRoot(array('name' => 'Sprzęt'));
	$id_rtv = $oTree->appendTo($id_sprzet, array('name' => 'RTV'));
	$id_agd = $oTree->appendTo($id_sprzet, array('name' => 'AGD'));

	$id_tv = $oTree->appendTo($id_rtv, array('name' => 'TV'));
	$id_radio = $oTree->insertAfter($id_tv, array('name' => 'Radio'));
	$id_kasety = $oTree->appendTo($id_radio, array('name' => 'Kasety'));
	$id_cd = $oTree->prependTo($id_radio, array('name' => 'CD'));

	$id_lodowki = $oTree->prependTo($id_agd, array('name' => 'Lodówki'));
	$id_odkurzacze = $oTree->prependTo($id_agd, array('name' => 'Odkurzacze'));
	$id_czajniki = $oTree->prependTo($id_agd, array('name' => 'Czajniki'));
	$id_elektryczne = $oTree->prependTo($id_czajniki, array('name' => 'Czajniki elektryczne'));
	$id_gwizdek = $oTree->prependTo($id_czajniki, array('name' => 'Czajniki na gwizdek'));

	$id_nagrywane = $oTree->appendTo($id_kasety, array('name' => 'Nagrywanie'));
}

//$oTree->insertChildAtIndex(142, 4, array('name' => 'co4'));
$oTree->moveLeft(9);

//$oTree->deleteNode(129);
$nodes = $oTree->getTree();
//echo '<pre>';var_dump($nodes);echo '</pre>';exit;
$view = new View(__DIR__ . '/views/NestedSetsTest.php');
$view->set('nodes', $nodes);
echo $view;


$oTree = new Kategoria('tree', $pdo);
$nodes = $oTree->getChildAtIndex(1, 1);
// $view = new View(__DIR__ . '/views/NestedSetsTest.php');
// $view->set('nodes', $nodes);
// echo $view;

var_dump($nodes);
