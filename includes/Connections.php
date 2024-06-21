<?php

/**
 * @package  TeachingAndLearningStuffBalanceTransfers
 */

namespace Inc;

use Inc\Config;



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
	/** @var object $conn */
	public $conn; // this is the connection to the database (or null if failed to connect)

	public $showConnection = true;

	// connection parameters are stored in connection strings on Azure
	// or <VirtualHost> directive in localhost environment
	private $connAppName;
	private $connStringName;
	private $envString;
	private $connString;
	private $certPath; // optional Custom connection string CUSTOMCONNSTR_{connAppName}_CA-cert

	private $myDatabase;
	private $myHostName;
	private $myUserName;
	private $myPassword;
	private $myDatabasePort;

	
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
		
		$config = new Config();

		$this->envString = $this->connAppName . "_" . $this->connStringName;
		$cstr = getenv("MYSQLCONNSTR_" . $this->envString);
		if($cstr){
			$this->connString = $cstr;
		}else{
			// try looking for environment variable with underscores instead of dashes
			$cstr = getenv("MYSQLCONNSTR_" . str_replace("-", "_", $this->envString));
			if($cstr){
				$this->connString = $cstr;
			}
		}
		
		$cpath = getenv("CUSTOMCONNSTR_" . $this->connAppName . "_" . "CA-cert"); // false if not found; string if found
		if($cpath){
			$this->certPath = $cpath;
		}else{
			// try looking for environment variable with underscores instead of dashes
			$cpath = getenv("CUSTOMCONNSTR_" . str_replace("-", "_", $this->connAppName) . "_" . str_replace("-", "_", "CA-cert")); // false if not found; string if found
			if($cpath){
				$this->certPath = $cpath;
			}
		}
		
		if($this->certPath){
			$this->certPath = $config->siteRoot . preg_replace("/^\./", "", $this->certPath); // fully qualified file path instead of relative
		}

		if($this->connString){
			// Parse to retrieve host, username, password and database.
			$this->myDatabase = preg_replace("/^.*Database=(.+?);.*$/", "\\1", $this->connString);
			$this->myHostName = preg_replace("/^.*Data Source=(.+?);.*$/", "\\1", $this->connString); // Data Source in Azure connection string
			$this->myUserName = preg_replace("/^.*User Id=(.+?);.*$/", "\\1", $this->connString);
			$this->myPassword = preg_replace("/^.*Password=(.+?)$/", "\\1", $this->connString);
			$this->myDatabasePort = 3306;
		
			// NEVER
			// show connection strings publicly!
			$env = getenv("APPSETTING_" . $this->connAppName . "_" . 'environment');
			if($env){
				$environment = $env;
			}

			// Apache may replace - (dash) with _ (underscore) in environment variable names, 
			// so look for environment names with underscores if the above failed
			// but build enVars with dashed array indexes
			if(! $env){
				$appUnderscore	= str_replace("-", "_", $this->connAppName);
				$nUnderscore	= str_replace("-", "_", 'environment');
				$env = getenv("APPSETTING_" . $appUnderscore . "_" . $nUnderscore);
				if($env){
					$environment = $env;
				}
			}

			if($environment == 'local'){
				$this->showConnection = true;
			}
			
			/** OPEN CONNECTION TO DATABSE WITH MySQLi */
			$this->conn = @$this->openConn();
		}else{
			$this->conn = null;
		}

	}

	function __destruct(){
		$this->closeConn();
	}


	private function openConn() : ?object {
		
		$link = null; // SSL connection will set link to true (success) or false (failure)

		if($this->certPath){
			// SSL connection with certificate
			// echo "<div class='hint'>SSL connection with certificate: " . $this->certPath . "</div>\n";

			// $this->myHostName = "teachingstuff-internalmysql.mysql.database.azure.com";
			// $this->myUserName = "tlsinternaladmin@teachingstuff-internalmysql";
			// $this->myPassword = "9xnS8f7E!MvYH9WwSXXy@AKHqtFxTPF@gr#GegOa9@fHlNFw\$F" ;
			// $this->myDatabase = "ssl_test";
			// $this->certPath = realpath('./cert/BaltimoreCyberTrustRoot.crt.pem'); // oldest
			// $this->certPath = realpath('./cert/DigiCertGlobalRootG2.crt.pem'); // older
			// $this->certPath = realpath('./cert/DigiCertGlobalRootCA.crt.pem'); // newer
			$conn = mysqli_init();
			mysqli_ssl_set($conn, NULL, NULL, $this->certPath, NULL, NULL); // always returns true
			$link = mysqli_real_connect($conn, $this->myHostName, $this->myUserName, $this->myPassword, $this->myDatabase, $this->myDatabasePort); // or die("Real Connect failed: #" . $conn->connect_errno . " -- " . $conn->connect_error);
		}else{
			// Non-SSL connection
			// echo "<div class='redText hint'><strong>Non-SSL</strong> connection: no CA certificate</div>";

			// $this->myHostName	= "localhost";
			// $this->myUserName	= "user";
			// $this->myPassword	= "pass";
			// $this->myDatabase	= "basicdatabase";
			$conn = new \MySQLi($this->myHostName, $this->myUserName, $this->myPassword, $this->myDatabase, $this->myDatabasePort) or die("Connect failed:\n". $conn->error);
		}

		// check connection
		if($conn->connect_error || $link === false){
			if($this->showConnection){
				echo "<div class='debugOutput'>Failed to connect to MySQLi: #" . $conn->connect_errno . " &mdash; " . $conn->connect_error . "</div>"; // dev: show reason
			}else{
				echo "<div class='debugOutput'>Failed to connect.</div>"; // live: hide reason
			}
			return null;
		}
	
		return $conn;
	}

	private function closeConn(){
		if($this->conn){
			$this->conn->close();
		}
	}
	





	#########################
	# Methods				#
	#########################
	public function showConnString() : string {
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