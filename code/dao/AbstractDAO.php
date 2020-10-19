<?php

require_once('Entities.php');
require_once('ConfigDAO.php');

abstract class AbstractDAO {
    protected static $connection;

    protected static function getConnection() {
        $config = new Config(dirname(dirname(__DIR__)) . '/config/');
        
	if (!isset(self::$connection)) {
		$dbSettings = parse_ini_file("settings.ini");
		$dsn = 'mysql:host=' . $config->getConfig('database.host') . ';dbname=' . $config->getConfig('database.name') . ';charset=utf8';
		self::$connection = new PDO($dsn, $config->getConfig('database.username'), $config->getConfig('database.password'), array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
	}
		return self::$connection;
	}
    
    protected static function check_isInt($variable){
      if( filter_var($variable, FILTER_VALIDATE_INT) === false ) {
        return false ;
      }
      return true;
    }

    protected static function query($connection, $query, $parameters = array(), $checkSuccess = false) {
		$statement = $connection->prepare($query);
		foreach ($parameters as $name => $value) {
			$statement->bindValue(
				is_int($name) ? $name + 1 : $name,
				$value,
				is_int($value)? PDO::PARAM_INT : PDO::PARAM_STR
			);
		}
		$success = $statement->execute();

        if ($checkSuccess) {
            return array(
                'success' => $success,
                'statement' => $statement,
                'rowCount' => $statement->rowCount());
        } else {
            return $statement;
        }
	}

    protected static function lastInsertId($connection) {
		return $connection->lastInsertId();
	}

    protected static function fetchObject($cursor) {
		return $cursor->fetchObject();
	}

    protected static function close($cursor) {
		$cursor->closeCursor();
	}
}
