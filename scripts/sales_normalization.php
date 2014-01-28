<?php

$archive="trans_archive";
$years =range(2006,2014);
$months= range(01,12);
$archdb = new mysqli("localhost", "root", "relax", $archive);

function update_ffa_department(){
global $years, $months, $archdb, $archive;
	echo "do all sum tenders by day: <br>";
	foreach($years as $year){
		foreach($months as $month){
			$query="UPDATE `trans_archive$year$month` SET`department`=48 WHERE  `upc` LIKE  '%ffa%';";
			echo $query;
			echo "<br>";
    		$archdb->query($query);
}}}

function update_maddiscount(){
global $years, $months, $logdb, $log, $archive;
	echo "do all sum discounts by day: <br>";
	foreach($years as $year){
		foreach($months as $month){
		$query="UPDATE `trans_archive$year$month` SET`department`=49 WHERE  `upc` =  'MADDISCOUNT";
			echo $query;
			echo "<br>";
    		$archdb->query($query);

			echo $query;
			echo "<br>";
    		$logdb->query($query);
}}};


?>

