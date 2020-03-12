<?php

/**
 * @package  TeachingAndLearningStuffBasicProject
 */

namespace Inc;



/**
 * Obtain connection string(s) from environment variables.  Establish connection to database with mysqli
 *
 * must provide connAppName (prefix) and connStringName
 *
 * concatenated with underscore to become:
 *
 * this-project_connection-string-1 or this-project_connection-string-2
 *
 * suggestion: $connection = new Connections(...);
 *
 * $conn = $connection->conn;
 *
 */
Class Connections {
	#########################
	# Properties 			#
	#########################
	public $conn; // this is the connection to the database (or null if failed to connect)

	public $showConnection = false;

	// connection parameters are stored in connection strings on Azure
	// or <VirtualHost> directive in localhost environment
	private $connAppName;
	private $connStringName;
	private $envString;
	private $connString;

	
	#########################
	# Initializers 			#
	#########################
	/**
	 * @param string $connAppName must match Azure app configuration: Connection string
	 * @param string $connStringName is individual connection string to retrieve (ex: 'mysql-database', but could be connections to other services/APIs)
	 */
	function __construct(string $connAppName, string $connStringName){
		$this->connAppName		= $connAppName;
		$this->connStringName	= $connStringName;

		$this->envString	= $this->connAppName . "_" . $this->connStringName;
		$this->connString	= getenv("MYSQLCONNSTR_" . $this->envString);

		if($this->connString){
			// Parse to retrieve host, username, password and database.
			$myDatabase = preg_replace("/^.*Database=(.+?);.*$/", "\\1", $this->connString);
			$myHostName = preg_replace("/^.*Data Source=(.+?);.*$/", "\\1", $this->connString); // Data Source in Azure connection string
			$myUserName = preg_replace("/^.*User Id=(.+?);.*$/", "\\1", $this->connString);
			$myPassword = preg_replace("/^.*Password=(.+?)$/", "\\1", $this->connString);
		
			// $myPassword = "foo";
			// NEVER
			// show connection strings publicly!
			$environment = getenv("APPSETTING_" . $this->connAppName . "_" . 'environment');
			if($environment == 'local'){
				$this->showConnection = true;
			}
			
			
			
			/** OPEN CONNECTION TO DATABSE WITH MySQLi */
			$this->conn = @$this->openConn($myHostName, $myUserName, $myPassword, $myDatabase);
		}else{
			$this->conn = null;
		}

	}

	function __destruct(){
		$this->closeConn();
	}


	private function openConn(string $theHostName, string $theUserName, string $thePassword, string $theDatabase) : ?object {
		//FIXME: adjust Connections class to use secure database connection, with OOP mysqli
		
		// $theHostName	= "localhost";
		// $theUserName	= "user";
		// $thePassword	= "pass";
		// $theDatabase	= "basicdatabase";
		$conn = new \MySQLi($theHostName, $theUserName, $thePassword, $theDatabase) or die("Connect failed:\n". $conn->error);
	
		// Check connection
		if($conn->connect_error){
			if($this->showConnection){
				echo "<div class='debugOutput'>Failed to connect to MySQLi: " . $conn->connect_errno . " &mdash; " . $conn->connect_error . "</div>"; // dev: show reason
			}else{
				echo "<div class='debugOutput'>Failed to connect.</div>"; // live: hide reason
			}
			return null;
		}
	
		return $conn;
	}

	private function closeConn(){
		if($this->conn){
			$this->conn -> close();
		}
	}
	





	#########################
	# Methods				#
	#########################
	function showConnString() : string {
		if($this->showConnection == false){
			return "";
		}

		$split = array_filter(explode("; ", $this->connString));
	
		$table  = "<div class='debugOutput'>";
		$table .= "<table border='1' cellpadding='2' cellspacing='0'>";
		$table .= "<thead>";
		$table .= "<tr><th class='thr'><code><strong>this->connString</strong></th><td>" . ($this->connString ? $this->connString : "<span class='notFound'>$this->envString</span>") . "</td></tr>";
		$table .= "</thead>";
		$table .= "<tbody>";
		if(count($split) == 0){
			$table.= "<tr><td colspan='2'>Connection String is not found</td></tr>";
		}else{
			foreach($split as $s){
				$pair = explode("=", $s);
				$table .= "<tr><th class='thr'>" . $pair[0] . "</th><td>" . $pair[1] . "</td></tr>";
			}
		}
		$table .= "</tbody></table>";
		$table .= "</div>";
	
		return $table;
	}



	/**
	 * given an arry of tables, and a field, along with an optional default value and 'as' build:
	 *
	 * COALESCE tableA.field, tableB.field, tableC.field, [default]) AS [alias]
	 *
	 * @param array $tables array of tables (tableA, tableB...)
	 * @param string $field field to concatenate with table (tableA.field, tableB.field)
	 * @param string|int|null $defaultValue optional value as last fallback
	 * @param string|null $as optional SELECT COALESCE(...) AS ??? clause
	 *
	 * @return string COALESCE clause for MySQL statement
	 */
	static public function buildCoalesceTables(array $tables, string $field, $defaultValue=null, ?string $theAs=null) : string {
		$each = array();
		foreach($tables as $t){
			array_push($each, $t . "." . $field);
		}
		if($defaultValue !== null){
			if(! preg_match("/^[0-9.]+$/", $defaultValue)){
				$defaultValue = "'" . $defaultValue . "'";
			}
			array_push($each, $defaultValue);
		}
		$built  = "COALESCE(";
		$built .= join(", ", $each);
		$built .= ")";
		if($theAs != null){
			$built .= " AS $theAs";
		}else{
			$built .= " AS $field";
		}
		
		return $built;
	}

	

}

?>