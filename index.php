<html>
	<head>
		<title>Top 10 : Teaching & Learning Stuff</title>
		<link rel="stylesheet" type='text/css' href='/styles/css/styles.css' />
		
		<style>
		</style>
	</head>

<body>

<?php
//FIXME: Remove ERROR Display from production code!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// composer autoload
require_once('./vendor/autoload.php');

use Inc\Config      AS CONFIG;
use Inc\Settings    AS SETTINGS;
use Inc\Connections AS CONNECTIONS;

$config     = new CONFIG(); // optionally send current-working-directory
$settings   = new SETTINGS('sms', array('environment')); // list all desired environment variables
$connection = new CONNECTIONS('sms', 'mysql-database'); // connection string to database is named 'basic-project_mysql-database'
$conn       = $connection->conn;



// Try to show 10 top items for a category
// first get 10 'top' items
// then get 2 'bottom' items
// finally, only if needed, get enough general items to reach 10
// replace/add lowest 2 items with the bottom items

// wanted to show 2 'new' items but it's too hard to query that based on firstRcvd being so frequently wrong

$category = (isset($_GET['category']) ? $_GET['category'] : ""); // category comes from URL

// sanitize
$categoryWhiteList = array();
$query = "SELECT DISTINCT category FROM commonAttributes";
// if($settings->showDebug) echo "<div class='debugOutput'><pre>$query</pre></div>";
if($result = $conn->query($query)){
	$rowCount = $conn->affected_rows;
	while ($row = $result->fetch_assoc()) {
		if($row['category'] == "") continue; // skip "" category
		array_push($categoryWhiteList, $row['category']);
	}
}
if(! in_array($category, $categoryWhiteList)) $category = '';

$itemCount		= 10;
$bottomCount	= 2;
$minOH			= 2;
$minSold		= 1;
$notIn			= array(); // accrues iems retrieved to prevent duplicates in subsequent queries

$resultTOP		= array();
$resultBOTTOM	= array();
$resultFILLER	= array();


?>

<article>

<?php

if($category == ""){
	echo "<h1>Please choose a category</h1>";
	echo "<p>show a list...</p>";
}else{
	// dynamically get list of retail locations
	$locationsList = array();
	$query = "SELECT code FROM locations WHERE type IN ('retail') ORDER BY number ASC";
	// if($settings->showDebug) echo "<div class='debugOutput'><pre>$query</pre></div>";
	if($result = $conn->query($query)){
		$rowCount = $conn->affected_rows;
		while ($row = $result->fetch_assoc()) {
			array_push($locationsList, $row['code']);
		}
	}
	
	
	
	// pre-build location-based clauses to use in queries
	// such as main_oh, bell_oh, tt06_oh, pv_oh
	
	// …_oh,
	$oh = array();
	foreach($locationsList as $location){
		array_push($oh, $location . "_oh");
	}
	$ohJoined = join(", ", $oh);
	// …_sold + …_sold + …
	$ohSUMjoined = join(" + ", $oh);
	
	// …_sold,
	$soldJoined = preg_replace("/_oh/", "_sold", $ohJoined);
	// …_sold + …_sold + …
	$soldSUMjoined = preg_replace("/_oh/", "_sold", $ohSUMjoined);
	
	// …_firstRcvd,
	$firstRcvdJoined = preg_replace("/_oh/", "_firstRcvd", $ohJoined);
	
	// >= clauses (for WHERE conditions)
	// …_oh > X AND …_oh > X AND …
	$ohGTminOH = array();
	foreach($locationsList as $location){
		array_push($ohGTminOH, $location . "_oh > $minOH");
	}
	$ohGTminOHJoined = join(" AND ", $ohGTminOH);
	// …_sold >= X AND …_sold >= X AND …
	$soldGTEminSoldJoined = preg_replace("/_oh > [0-9]+/", "_sold >= ".$minSold, $ohGTminOHJoined);
	
	
	
	// MOST POPULAR BY TOTAL SOLD
	// everyone has > 2 on hand
	// everyone has sold at least 1
	// order by company-wide total most sold
	$query = "SELECT 
	mergedForBalance.item, 
	commonAttributes.title, 
	commonAttributes.price, 
	$ohJoined, 
	LEAST($ohJoined) AS smallestOH, 
	$soldJoined, 
	($soldSUMjoined) AS totalSold, 
	$firstRcvdJoined, 
	LEAST($firstRcvdJoined) AS oldestRcvd
FROM mergedForBalance 
LEFT JOIN commonAttributes ON mergedForBalance.item = commonAttributes.item 
WHERE 
	commonAttributes.category = '$category' 
	AND ($ohGTminOHJoined) 
	AND ($soldGTEminSoldJoined) 
ORDER BY 
	totalSold DESC, 
	smallestOH DESC
LIMIT $itemCount";

	$debugTable = "";
	// if($settings->showDebug) echo "<div class='debugOutput'><pre>$query</pre></div>";
	if($result = $conn->query($query)){
		$rowCountTOP = $conn->affected_rows;
		$debugTable .= "<table border='1' cellpadding='2' cellspacing='0'>";
		$debugTable .= "<thead>";
		$debugTable .= "<tr><th colspan='7'>" . number_format($rowCountTOP, 0) . " TOP Seller" . ($rowCountTOP != 1 ? "s" : "") . "<br /><code style='font-weight: normal;'>OH&gt;$minOH and sold&gt;=$minSold</code></th></tr>";
		$debugTable .= "<tr><th class='tdl'>item</th><th class='tdl'>Title</th><th class='tdr'>min/max OH</th><th class='tdr'>min/max SOLD</th><th class='tdr'>total sold</th><th class='tdr'>Price</th></tr>";
		$debugTable .= "</thead>";
		while ($row = $result->fetch_assoc()) {
			array_push($notIn, "'".$row['item']."'");
			array_push($resultTOP, $row);
			$debugTable .= "<tr>";
			$debugTable .= "<td>" . $row['item'] . "</td><td>" . $row['title'] . "</td>";
			$debugTable .= "<td>" . minOH($row, $locationsList) . "/" . maxOH($row, $locationsList) . "</td>";
			$debugTable .= "<td>" . minSold($row, $locationsList) . "/" . maxSold($row, $locationsList) . "</td>";
			$debugTable .= "<td class='tdr'>" . number_format($row['totalSold'], 0) . "</td><td class='tdr'>" . $row['price'] . "</td>";
			$debugTable .= "</tr>";
		}
		$debugTable .= "</table>";
	}else{
		$rowCountTOP = 0;
		if($settings->showDebug) echo "<p class='sqlError'>Query failed.  (" . $conn->errno . ") " . $conn->error . "</p>";
	}
	if($settings->showDebug) echo $debugTable;



	// LEAST POPULAR BY TOTAL SOLD
	// everyone has > 2 on hand
	// sold can be 0
	// order by company-wide total least sold
	$soldGTEminSoldJoined = preg_replace("/>= [0-9]+/", ">= ".max(0, floor($minSold/2)), $soldGTEminSoldJoined); // ease up on minSold requirement
	$notInJoined = join(", ", $notIn);
	$notInClause = (count($notIn) == 0 ? "" : "AND mergedForBalance.item NOT IN ($notInJoined)");
	$query = "SELECT 
	mergedForBalance.item, 
	commonAttributes.title, 
	commonAttributes.price, 
	$ohJoined, 
	LEAST($ohJoined) AS smallestOH, 
	$soldJoined, 
	($soldSUMjoined) AS totalSold, 
	$firstRcvdJoined,
	LEAST($firstRcvdJoined) AS oldestRcvd
FROM mergedForBalance 
LEFT JOIN commonAttributes ON mergedForBalance.item = commonAttributes.item 
WHERE 
	commonAttributes.category = '$category' 
	AND ($ohGTminOHJoined) 
	AND ($soldGTEminSoldJoined) 
	$notInClause
ORDER BY 
	totalSold ASC,  
	($ohSUMjoined) DESC
LIMIT $bottomCount";

	$debugTable = "";
	// if($settings->showDebug) echo "<div class='debugOutput'><pre>$query</pre></div>";
	if($result = $conn->query($query)){
		$rowCountBOTTOM = $conn->affected_rows;
		$debugTable .= "<table border='1' cellpadding='2' cellspacing='0'>";
		$debugTable .= "<thead>";
		$debugTable .= "<tr><th colspan='7'>" . number_format($rowCountBOTTOM, 0) . " BOTTOM Seller" . ($rowCountBOTTOM != 1 ? "s" : "") . "<br /><code style='font-weight: normal;'>OH&gt;$minOH and sold&gt;=0</code></th></tr>";
		$debugTable .= "<tr><th class='tdl'>item</th><th class='tdl'>Title</th><th class='tdr'>min/max OH</th><th class='tdr'>min/max SOLD</th><th class='tdr'>total sold</th><th class='tdr'>Price</th></tr>";
		$debugTable .= "</thead>";
		while ($row = $result->fetch_assoc()) {
			array_push($notIn, "'".$row['item']."'");
			array_push($resultBOTTOM, $row);
			$debugTable .= "<tr>";
			$debugTable .= "<td>" . $row['item'] . "</td><td>" . $row['title'] . "</td>";
			$debugTable .= "<td>" . minOH($row, $locationsList) . "/" . maxOH($row, $locationsList) . "</td>";
			$debugTable .= "<td>" . minSold($row, $locationsList) . "/" . maxSold($row, $locationsList) . "</td>";
			$debugTable .= "<td class='tdr'>" . number_format($row['totalSold'], 0) . "</td><td>" . $row['price'] . "</td>";
			$debugTable .= "</tr>";
		}
		$debugTable .= "</table>";
	}else{
		$rowCountBOTTOM = 0;
		if($settings->showDebug) echo "<p class='sqlError'>Query failed.  (" . $conn->errno . ") " . $conn->error . "</p>";
	}
	if($settings->showDebug) echo $debugTable;





	// BROADEST SET OF ITEMS IN THIS CATEGORY
	// everyone has > 1 on hand
	// sold can be 0
	// order by company-wide most on hand 
	// 			company-wide most sold
	$ohGTminOHJoined = preg_replace("/> [0-9]+/", "> ".max(0, floor($minOH/2)), $ohGTminOHJoined); // ease up on minOH requirement
	$limit = max(0, $itemCount - $rowCountTOP - $rowCountBOTTOM);
	$notInJoined = join(", ", $notIn);
	$notInClause = (count($notIn) == 0 ? "" : "AND mergedForBalance.item NOT IN ($notInJoined)");
	$query = "SELECT 
	mergedForBalance.item, 
	commonAttributes.title, 
	commonAttributes.price, 
	$ohJoined, 
	LEAST($ohJoined) AS smallestOH, 
	$soldJoined, 
	($soldSUMjoined) AS totalSold, 
	$firstRcvdJoined, 
	LEAST($firstRcvdJoined) AS oldestRcvd
FROM mergedForBalance 
LEFT JOIN commonAttributes ON mergedForBalance.item = commonAttributes.item 
WHERE 
	commonAttributes.category = '$category' 
	AND ($ohGTminOHJoined) 
	$notInClause 
ORDER BY 
	totalSold DESC,
	smallestOH DESC 
LIMIT $limit";

	$debugTable = "";
	// if($settings->showDebug) echo "<div class='debugOutput'><pre>$query</pre></div>";
	if($result = $conn->query($query)){
		$rowCountFILLER = $conn->affected_rows;
		$debugTable .= "<table border='1' cellpadding='2' cellspacing='0'>";
		$debugTable .= "<thead>";
		$debugTable .= "<tr><th colspan='7'>" . number_format($rowCountFILLER, 0) . " FILLER Seller" . ($rowCountFILLER != 1 ? "s" : "") . "<br /><code style='font-weight: normal;'>OH&gt;" . max(0, floor($minOH/2)) . "</th></tr>";
		$debugTable .= "<tr><th class='tdl'>item</th><th class='tdl'>Title</th><th class='tdr'>min/max OH</th><th class='tdr'>min/max SOLD</th><th class='tdr'>total sold</th><th class='tdr'>Price</th></tr>";
		$debugTable .= "</thead>";
		while ($row = $result->fetch_assoc()) {
			array_push($notIn, "'".$row['item']."'");
			array_push($resultFILLER, $row);
			$debugTable .= "<tr>";
			$debugTable .= "<td>" . $row['item'] . "</td><td>" . $row['title'] . "</td>";
			$debugTable .= "<td>" . minOH($row, $locationsList) . "/" . maxOH($row, $locationsList) . "</td>";
			$debugTable .= "<td>" . minSold($row, $locationsList) . "/" . maxSold($row, $locationsList) . "</td>";
			$debugTable .= "<td class='tdr'>" . number_format($row['totalSold'], 0) . "</td><td class='tdr'>" . $row['price'] . "</td>";
			$debugTable .= "</tr>";
		}
		$debugTable .= "</table>";
	}else{
		$rowCountFILLER = 0;
		if($settings->showDebug) echo "<p class='sqlError'>Query failed.  (" . $conn->errno . ") " . $conn->error . "</p>";
	}
	if($settings->showDebug) echo $debugTable;





	$finalList = array();
	
	// top sellers
	$counter = 0;
	foreach($resultTOP as $row) {
		$finalList[$counter] = $row;
		$counter++;
	}
	
	// filler as needed
	foreach($resultFILLER as $row) {
		if(count($finalList) >= $itemCount) break;
		$finalList[$counter] = $row;
		$counter++;
	}
	
	// echo "<div><pre>"; print_r($finalList); echo "</pre></div>\n";
	
	// echo "<div>count=" . count($finalList) . " &gt; $itemCount - $rowCountBOTTOM (" . ($itemCount - $rowCountBOTTOM) . ")</div>\n";
	if(count($finalList) > ($itemCount - $rowCountBOTTOM)){
		// echo "<div>count=" . count($finalList) . " &lt; $itemCount</div>\n";
		if(count($finalList) < $itemCount){
			// echo "<div>abs=" . abs(count($finalList) - $itemCount) . " &gt; $rowCountBOTTOM</div>\n";
			if(abs(count($finalList) - $itemCount) > $rowCountBOTTOM){
				$index = count($finalList) + $rowCountBOTTOM - 1;
			}else{
				$index = $itemCount - 1;
			}
		}else{
			$index = $itemCount - 1;
		}
	}else{
		$index = count($finalList) + $rowCountBOTTOM - 1;
	}
	
	// prepare empty slots at end to receive bottom items
	while(count($finalList) - 1 < $index){
		$finalList[] = "";
	}
	
	$counter = 0;
	for($r=count($resultBOTTOM)-1; $r>=0; $r--) {
		// echo "<div>index=$index | r=$r</div>\n";
		$finalList[$index] = $resultBOTTOM[$r]; // ($rowCountBOTTOM-1 - $counter) - ($rowCountBOTTOM - $counter)
		$index--;
		$counter++;
	}
	
	
	
	
	
	//TODO: it should have a lookup table for categories so it shows `Social Studies` instead of 'SOCIALSTUD`
	if(count($finalList) == 0){
		echo "<h1>We Couldn't Find Any Items for " . ucWords($category) . " Right Now</h1>";
	}else{
		echo "<h1>Top " . ((count($finalList) == $itemCount) ? number_format($itemCount, 0) : " Selling") . " Item" . (count($finalList) != 1 ? "s" : "") . " For " . ucwords($category) . "</h1>";
		echo "<table border='1' cellpadding='2' cellspacing='0'>";
		$num = 1;
		foreach($finalList as $row){
			echo "<tr><th class='tdr'>" . number_format($num, 0) . "</th><td>" . $row['item'] . "</td><td>" . $row['title'] . "</td><td>" . number_format($row['price'], 2) . "</td></tr>";
			$num++;
		}
		echo "</table>";
		// echo "<div><pre>"; print_r($finalList); echo "</pre></div>\n";
	}
}





function minSold(array $row, array $locationsList) : int {
	$min = 999999999;
	foreach($locationsList as $location){
		$min = min($row[$location."_sold"], $min);
	}

	return $min;
}



function maxSold(array $row, array $locationsList) : int {
	$max = -999999999;
	foreach($locationsList as $location){
		$max = max($row[$location."_sold"], $max);
	}

	return $max;
}





function minOH(array $row, array $locationsList) : int {
	$min = 999999999;
	foreach($locationsList as $location){
		$min = min($row[$location."_oh"], $min);
	}

	return $min;
}



function maxOH(array $row, array $locationsList) : int {
	$max = -999999999;
	foreach($locationsList as $location){
		$max = max($row[$location."_oh"], $max);
	}

	return $max;
}



if($settings->showDebug) echo "<p>done</p>";

?>

</article>
</body>
</html>