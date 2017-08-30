<?php

require_once('Entities.php');

require_once('ConfigDAO.php');

abstract class AbstractDAO {
    protected static $connection;
    
    protected static function getConnection() {
        $config = new Config(dirname(dirname(__DIR__)) . '/config/');
        
        if($config->getConfig('database.extension') == 'pdo') {
            if (!isset(self::$connection)) {
            $dsn = 'mysql:host=' . $config->getConfig('database.host') . ';dbname=' . $config->getConfig('database.name') . ';charset=utf8';
            self::$connection = new PDO($dsn, $config->getConfig('database.username'), $config->getConfig('database.password'), array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            }
        } elseif($config->getConfig('database.extension') == 'mysqli') {
            self::$connection = new mysqli($config->getConfig('database.host'),$config->getConfig('database.username'),$config->getConfig('database.password'),$config->getConfig('database.name'));
		} else {
            return;
        }
		return self::$connection;
	}

    protected static function query($connection, $query, $parameters = array(), $checkSuccess = false) {
		$statement = $connection->prepare($query);
		foreach ($parameters as $name => $value) {
			$statement->bindValue(
				is_int($name) ? $name + 1 : $name,
				$value,
				PDO::PARAM_INT
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
        
        $config = new Config(dirname(dirname(__DIR__)) . '/config/');
        if($config->getConfig('database.extension') == 'pdo') {
            return $connection->lastInsertId();
        } elseif($config->getConfig('database.extension') == 'mysqli') {
            return $connection->insert_id;
		}
	}

    protected static function fetchObject($cursor) {
        
        $config = new Config(dirname(dirname(__DIR__)) . '/config/');
        if($config->getConfig('database.extension') == 'pdo') {
            return $cursor->fetchObject();
        } elseif($config->getConfig('database.extension') == 'mysqli') {
            return $cursor->fetch_object();
		}
	}

    protected static function close($cursor) {
		$cursor->closeCursor();
	}
}