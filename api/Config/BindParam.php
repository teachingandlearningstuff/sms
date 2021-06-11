<?php

namespace API\Config;


Class BindParam{
	private $values = array();
	private $types = '';
	
	public function add( $type, &$value ){
		$this->values[] = &$value;
		$this->types .= $type;
	}

	public function get() : ?array {
		$returnArray = array_filter(array_merge(array($this->types), $this->values));

		if(count($returnArray) == 0) return null;
		else return $returnArray;
	}
}


/*

if($stmt = $this->conn->prepare($query)){
	// i=integer, d=double, s=string, b=blob
	// print_r($bindParam->get()); echo "\n";
	$result = null;
	if($bindParam->get()){
		// dynamically bind parameters
		if(call_user_func_array(array($stmt, 'bind_param'), $bindParam->get())){
			if($stmt->execute()){
				$result	= $stmt->get_result();
			}
		}
	}else{
		// no dynamic parameters to bind
		if($stmt->execute()){
			$result	= $stmt->get_result();
		}
	}
	// result will be null if call_user_func_array() or $stmt->execute() failed
	if($result){
		// echo "<div><pre>"; print_r($result); echo "</pre></div>\n";
		$rowCount = $stmt->affected_rows;
		// echo "$rowCount counted\n";
		if($rowCount > 0){
			while ($row = $result->fetch_assoc()) {
				$var = $row['key'];
			}
		}
	}
}

*/

?>