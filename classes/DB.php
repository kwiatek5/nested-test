<?php

class DB {

	private static $pdo;
	private static $dsn = 'mysql:dbname=testy_nested_sets;host=127.0.0.1';
	private static $user = 'root';
	private static $pass = '';

	private function __construct() {
		
	}

	private function __clone() {
		
	}

	private static function setPDO() {
		if (!isset(self::$pdo)) {
			self::$pdo = new PDO(self::$dsn, self::$user, self::$pass, array(
				array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"'),
			));

			self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		}
	}

	public static function q($sql, $params = array()) {
		self::setPDO();

		$query = self::$pdo->prepare($sql);
		$query->execute($params);
		return $query->fetchAll();
	}

	public static function x($sql, $params = array()) {
		self::setPDO();

		$query = self::$pdo->prepare($sql);
		$query->execute($params);
	}
	
	public static function lastID() {
		self::setPDO();
		
		return self::$pdo->lastInsertId();
	}

}