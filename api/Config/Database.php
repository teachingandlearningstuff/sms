<?php

namespace API\Config;

// composer autoload
$relativePath = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
$siteRoot = str_replace($relativePath, "", __DIR__);
require_once $siteRoot . '/vendor/autoload.php';

use Inc\Connections;

Class Database {
	public $connection; /** @var Connection $connection */
	public $conn; /** @var object $conn */
	

	/**
	 * free up resources on class destruct
	 */
	function __destruct(){
		$this->conn			= null;
		$this->connection	= null;
	}


	/**
	 * get database connection object
	 * 
	 * @return object database connection
	 */
	public function getConn(string $appName='sms', string $stringName='mysql-database'){
		$this->connection	= new Connections($appName, $stringName); // looks for connection string named '{{app-name}}_{{connection-string}}'
		$this->conn			= $this->connection->conn;

		return $this->conn;
	}
}

?>