<?php
/*********************************************************************************

Summary Migration will automate the process of r migrating your historical
POS sales data from older versions of IS4C into the appropriate DBs, tables, and 
views in order for CORE-POS reporting to function as expected.

This script will parse the old data into tables like transArchiveYYYYMM in the 
database trans_archive.  Also all the summary tables like sumUpcSalesByDay and
sumDeptSalesByDay and others.

***  SETTINGS  ******************************************************************/

# SOURCE DB
$log="is4c_log";
# TARGET DB
$archive="trans_archive";
# ENTER THE YEARS + MONTHS TO PARSE
$years =range(2006,2013);
$months= range(01,12);

# COMMENT / UNCOMMENT depending on which data to parse
#count_records_per_month();
#for_each_day();
#query_for_each_day();
#do_all_sum_upc_sales_by_day()
#do_all_sum_ring_sales_by_day();
do_all_sum_dept_sales_by_day();
#do_all_sum_mem_sales_by_day();
#do_all_sum_mem_types_sales_by_day();
#do_all_sum_tenders_by_day();
#do_all_sum_discounts_by_day();
#create_dept_sales();

/********************************************************************************/

$logdb = new mysqli("localhost", "root", "relax", $log);
$archdb = new mysqli("localhost", "root", "relax", $archive);
function count_records_per_month(){
	global $years, $months, $logdb;
	#$years=range(2006,2007);
	#$months=range(1,12);
	echo "records per month<br>";
foreach($years as $year){
	foreach($months as $month){
	$count = "SELECT * FROM `dlog_$year` WHERE MONTH(datetime) = $month";
	$result = $logdb->query($count);	
		$rows=$result->num_rows;
		echo "$year"." ".$month.": ".$rows."<br>";
	};
};
};
function for_each_day(){
	global $years, $months, $logdb;
	echo "for each day: <br>";
	foreach($years as $year){
	foreach($months as $month){
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$days=range(1,$days_in_month);
		
		foreach($days as $day){
			echo $day;
	};
	
		echo "<br>";
	};

};
};
function query_for_each_day(){
	global $years, $months, $logdb;
	echo "query for each day: <br>";
	foreach($years as $year){
	foreach($months as $month){
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$days=range(1,$days_in_month);
		
		foreach($days as $day){
			echo "SELECT * FROM `dlog_$year` WHERE MONTH(datetime) = $month and DAY(datetime)=$day";
		echo "<br>";
	};
	echo "<br>";
	};

};
};

function do_all_sum_upc_sales_by_day(){
	global $years, $months, $logdb, $log, $archive;
	echo "do all sum upc sales by day: <br>";
	foreach($years as $year){
		foreach($months as $month){
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$days=range(1,$days_in_month);
			foreach($days as $day){

				$query="INSERT INTO $archive.sumUpcSalesByDay
				SELECT datetime AS tdate, upc,
			CONVERT(SUM(total),DECIMAL(10,2)) as total,
			CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
				WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
			FROM $log.dlog_$year WHERE
			trans_type IN ('I') AND upc <> '0' AND MONTH(datetime) = $month AND DAY(datetime)=$day
			GROUP BY upc;";

			echo $query;
			echo "<br>";
    		$logdb->query($query);

}
}
}

}

function do_all_sum_ring_sales_by_day(){
	global $years, $months, $logdb, $log, $archive;
	echo "do all sum ring sales by day: <br>";
	foreach($years as $year){
		foreach($months as $month){
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$days=range(1,$days_in_month);
			foreach($days as $day){
			

			$query="INSERT INTO $archive.sumRingSalesByDay
			SELECT datetime AS tdate, upc, department,
			CONVERT(SUM(total),DECIMAL(10,2)) as total,
			CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
				WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
			FROM $log.dlog_$year WHERE
			trans_type IN ('I','D') AND upc <> '0' AND MONTH(datetime) = $month AND DAY(datetime)=$day
			GROUP BY upc, department;";
		
			echo $query;
			echo "<br>";
    		$logdb->query($query);

}
}
}

}

function do_all_sum_dept_sales_by_day(){
	global $years, $months, $logdb, $log, $archive;
	echo "do all sum dept sales by day: <br>";
	foreach($years as $year){
		foreach($months as $month){
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$days=range(1,$days_in_month);
			foreach($days as $day){

			$query="INSERT INTO $archive.sumDeptSalesByDay
				SELECT datetime AS tdate, department,
				CONVERT(SUM(total),DECIMAL(10,2)) as total,
				CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
					WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty
				FROM $log.dlog_$year WHERE
				trans_type IN ('I','D')  AND MONTH(datetime) = $month AND DAY(datetime)=$day
				GROUP BY department";

			echo $query;
			echo "<br>";
    		$logdb->query($query);


}}}}



function do_all_sum_mem_sales_by_day(){
	global $years, $months, $logdb, $log, $archive;
	echo "do all sum mem sales by day: <br>";
	foreach($years as $year){
		foreach($months as $month){
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$days=range(1,$days_in_month);
			foreach($days as $day){

			$query="INSERT INTO sumMemSalesByDay
			SELECT datetime AS tdate, card_no,
			CONVERT(SUM(total),DECIMAL(10,2)) as total,
			CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
				WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty,
			COUNT(DISTINCT trans_no) AS transCount
			FROM $log.dlog_$year WHERE
			trans_type IN ('I','D') AND MONTH(datetime) = $month AND DAY(datetime)=$day
			GROUP BY card_no";



			echo $query;
			echo "<br>";
    		$logdb->query($query);
}}}}

function do_all_sum_mem_types_sales_by_day(){
global $years, $months, $logdb, $log, $archive;
	echo "do all sum mem types sales by day: <br>";
	foreach($years as $year){
		foreach($months as $month){
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$days=range(1,$days_in_month);
			foreach($days as $day){

			$query="INSERT INTO sumMemTypeSalesByDay
			SELECT datetime AS tdate, c.memType,
			CONVERT(SUM(total),DECIMAL(10,2)) as total,
			CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
				WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as qty,
			COUNT(DISTINCT trans_no) AS transCount
			FROM $log.dlog_$year AS d LEFT JOIN
			is4c_op.custdata AS c ON d.card_no=c.CardNo
			AND c.personNum=1 WHERE
			trans_type IN ('I','D')
			AND upc <> 'RRR' AND card_no <> 0 AND MONTH(datetime) = $month AND DAY(datetime)=$day
			GROUP BY c.memType";



			echo $query;
			echo "<br>";
    		$logdb->query($query);
}}}}
function do_all_sum_tenders_by_day(){
global $years, $months, $logdb, $log, $archive;
	echo "do all sum tenders by day: <br>";
	foreach($years as $year){
		foreach($months as $month){
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$days=range(1,$days_in_month);
			foreach($days as $day){

			$query="INSERT INTO sumTendersByDay
			SELECT datetime AS tdate, trans_subtype,
			CONVERT(SUM(total),DECIMAL(10,2)) as total,
			COUNT(*) AS quantity
			FROM $log.dlog_$year WHERE
			trans_type IN ('T')
			AND total <> 0 AND MONTH(datetime) = $month AND DAY(datetime)=$day
			GROUP BY trans_subtype";



			echo $query;
			echo "<br>";
    		$logdb->query($query);
}}}}

function do_all_sum_discounts_by_day(){
global $years, $months, $logdb, $log, $archive;
	echo "do all sum discounts by day: <br>";
	foreach($years as $year){
		foreach($months as $month){
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$days=range(1,$days_in_month);
			foreach($days as $day){

			$query="INSERT INTO $archive.sumDiscountsByDay
			SELECT datetime AS tdate, c.memType,
			CONVERT(SUM(total),DECIMAL(10,2)) as total,
			COUNT(DISTINCT trans_no) AS transCount
			FROM $log.dlog_$year AS d LEFT JOIN
			is4c_op.custdata AS c ON d.card_no=c.CardNo
			AND c.personNum=1 WHERE
			trans_type IN ('S') AND total <> 0
			AND upc = 'DISCOUNT' AND card_no <> 0 AND MONTH(datetime) = $month AND DAY(datetime)=$day
			GROUP BY c.memType";



			echo $query;
			echo "<br>";
    		$logdb->query($query);
}}}};



function create_upc_sales(){global $archdb;
	echo "creating table.";
	$archdb -> query("CREATE TABLE sumUpcSalesByDay (
	tdate date,
	upc varchar(13),
	total decimal(10,2),
	quantity decimal(10,2),
	PRIMARY KEY (tdate, upc)
	)");};
function create_ring_sales(){global $archdb;
	echo "creating table.";
	$archdb -> query("CREATE TABLE sumRingSalesByDay (
	tdate date,
	upc varchar(13),
	dept int,
	total decimal(10,2),
	quantity decimal(10,2),
	PRIMARY KEY (tdate, upc, dept),
	INDEX(upc),
	INDEX(dept),
	INDEX(tdate)
	)");};
function create_dept_sales(){
	global $archdb;
	echo "creating table sumDeptSalesByDay.";
	$archdb -> query("CREATE TABLE sumDeptSalesByDay 
		(tdate date,
		dept_ID int,
		total decimal(10,2),
		quantity decimal(10,2),
		PRIMARY KEY (tdate, dept_ID))");
};
function create_mem_sales(){global $archdb;
	echo "creating table.";
	$archdb -> query("CREATE TABLE sumMemSalesByDay (
	tdate date,
	card_no int,
	total decimal(10,2),
	quantity decimal(10,2),
	transCount int,
	PRIMARY KEY (tdate, card_no)
	)");};
function create_mem_type_sales(){global $archdb;
	echo "creating table.";
	$archdb -> query("CREATE TABLE sumMemTypeSalesByDay (
	tdate date,
	memType smallint,
	total decimal(10,2),
	quantity decimal(10,2),
	transCount int,
	PRIMARY KEY (tdate, memType)
	)");};
function create_sum_tenders(){global $archdb;
	echo "creating table.";
	$archdb -> query("CREATE TABLE sumTendersByDay (
	tdate date,
	tender_code varchar(2),
	total decimal(10,2),
	quantity int,
	PRIMARY KEY (tdate, tender_code)
	)");};
function create_sum_discount(){global $archdb;
	echo "creating table.";
	$archdb -> query("	CREATE TABLE sumDiscountsByDay (
	tdate date,
	memType smallint,
	total decimal(10,2),
	transCount int,
	PRIMARY KEY (tdate, memType)
	)");};

?>