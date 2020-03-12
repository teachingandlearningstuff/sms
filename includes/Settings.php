<?php

/**
 * @package  TeachingAndLearningStuffBasicProject
 */

namespace Inc;



/**
 * Obtain environment variables (application settings)
 *
 * must provide appName (prefix) and list of settingsNames
 *
 * concatenated with underscore to become:
 *
 * this-project_setting-1 or this-project_setting-2
 */
Class Settings {
	#########################
	# Properties 			#
	#########################
	private	$appName;				// required ex: basic-project
	private	$enVars		= array();	// destination array for environment variables (application settings)
	
	public	$showDebug	= false;	// default false; set from application environment (local, dev, or production)

	
	#########################
	# Initializers 			#
	#########################
	/**
	 * appName is a prefix
	 *
	 * settingsNames lists environment variables to retrieve
	 *
	 * @param string $appName must match Azure app configuration: Application settings
	 */
	function __construct(string $appName, array $settingsNames){
		// application settings are stored on Azure
		$this->appName	= $appName;
		foreach($settingsNames as $n){
			$this->enVars[$n] = getenv("APPSETTING_" . $this->appName . "_" . $n);
		}

		if($this->enVars['environment'] == "local" || $this->enVars['environment'] == "dev"){
			$this->showDebug = true;
		}else{
			$this->showDebug = false;
		}

		// NEVER
		// show application settings publicly!
		// use showApplicationSettings() to see all environment variables that were requested
	}



	/**
	 * Usually you should rely on the enviroment setting to set this.
	 *
	 * @param bool $showDebug
	 */
	final public function setShowDebug(bool $showDebug) : void {
		$this->showDebug = $showDebug;
	}



	#########################
	# Methods				#
	#########################
	/**
	 * get an environment variable's value
	 *
	 * @param string $appName must match Azure app configuration: Application settings
	 * @return string|null returns string if found, or null if not found
	 */
	public function getEnVar(string $environmentVariableName) : ?string {
		if(in_array($environmentVariableName, array_keys($this->enVars))){
			return $this->enVars[$environmentVariableName];
		}else{
			return null;
		}
	}



	/**
	 * Show all application settings that were retrieved from environment variables
	 */
	public function showApplicationSettings() : string {
		$table  = "<div class='debugOutput'>";
		$table .= "<table border='1' cellpadding='2' cellspacing='0'>";
		$table .= "<thead>";
		$table .= "<tr><th class='thr'><code><strong>applicationSettings</strong></th><td>" . (count($this->enVars) > 0 ? "<em>array</em>" : "none requested") . "</td></tr>";
		$table .= "</thead>";
		$table .= "<tbody>";
		foreach($this->enVars as $key => $value){
			$table .= "<tr><th class='thr" . ($value ? "" : " notFound") . "'><span class='appName'>" . $this->appName . "_</span>" . $key . "</th><td>" . $value . "</td></tr>";
		}
		$table .= "</tbody></table>";
		$table .= "</div>";
	
		return $table;
	}
	

}

?>