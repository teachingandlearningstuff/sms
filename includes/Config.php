<?php

/**
 * @package  TeachingAndLearningStuffBasicProject
 */

namespace Inc;



/**
 * General Config for site
 *
 * Current Working Directory (cwd) can be sent from outside
 *
 * ex: Config($this->siteRoot . "/" . $this->projectDirectory);
 *
 */
Class Config {
	#########################
	# Properties 			#
	#########################
	public $siteRoot			= ""; // /includes
	public $relativePath		= ""; // /Applications/MAMP/htdocs/teachingstuff-downloads
	public $workingDirectory	= ""; // /Applications/MAMP/htdocs/teachingstuff-downloads/craftDays/turkeyClothespins
	public $currentDirectory	= ""; // /turkeyClothespins
	
	
	#########################
	# Initializers 			#
	#########################
	/**
	 * @param string $cwd optional Current-Working-Directory
	 */
	public function __construct(string $cwd=""){
		$dir	= __DIR__;
		$droot	= $_SERVER['DOCUMENT_ROOT'];
		
		$relativePath = substr($dir, strlen($droot));
		// $relativePath = str_replace("\\", "/", $relativePath);
		// echo "<div>RELATIVE PATH " . $relativePath . "</div>";
		
		$root = str_replace($relativePath, "", $dir);
		// $root = str_replace("\\", "/", $root);
		// echo "<div>ROOT: " . $root . "</div>";

		if($cwd == "") $cwd = getcwd();
		$cwd = str_replace("\\", "/", $cwd);
		$cwd = preg_replace("/\/$/", "", $cwd);
		$cd = strrchr($cwd, '/'); // everything including and after the last '/'
		// echo "<div>C_DIR: " . $cd . "</div>";

		$wd = str_replace("\\", "/", getcwd()); // everything including and after the last '/'
		// echo "<div>W_DIR: " . $wd . "</div>";

		$this->relativePath			= $relativePath;
		$this->siteRoot				= $root;
		$this->workingDirectory		= $wd;
		$this->currentDirectory		= $cd;
	}
}

?>