<?php
$log="is4c_log";
# TARGET DB
$archive="trans_archive";
# ENTER THE YEARS + MONTHS TO PARSE
$years = range(2013,2014);

$logdb = new mysqli("localhost", "root", "eng@ge", $log);
$archdb = new mysqli("localhost", "root", "eng@ge", $archive);

function move_2013(){
	global $years, $logdb, $log, $archive;
	echo "do all sum upc sales by day: <br>";
	$year='2013';	
	$months=array(9,10,11,12);	
	foreach($months as $month){
				$month=sprintf("%02d", $month);;
				$query="INSERT INTO trans_archive`transArchive$year$month`
				(`datetime`, `register_no`, `emp_no`, `trans_no`, `upc`, `description`, `trans_type`, `trans_subtype`, `trans_status`, `department`, `quantity`, `scale`, `cost`, `unitPrice`, `total`, `regPrice`, `tax`, `foodstamp`, `discount`, `memDiscount`, 
`discountable`, `discounttype`, `voided`, `percentDiscount`, `ItemQtty`, 
	`volDiscType`, `volume`, `VolSpecial`, `mixMatch`, `matched`, `memType`,
	`staff`, `numflag`, `card_no`, `trans_id`) SELECT 
				`datetime`, `register_no`, `emp_no`, `trans_no`, `upc`, `description`, `trans_type`, `trans_subtype`, `trans_status`, `department`, `quantity`, `Scale`, `cost`, `unitPrice`, `total`, `regPrice`, `tax`, `foodstamp`, `discount`, `memDiscount`, 
`discountable`, `discounttype`, `voided`, `percentDiscount`, `ItemQtty`,
 `volDiscType`, `volume`, `VolSpecial`, `mixMatch`, `matched`, `memType`,
 	 `staff`, `props` `card_no`, `trans_id` FROM is4c_log.`dlog_$year` WHERE MONTH(datetime) = $month";

			echo $query;
			echo "<br>";
    		#$logdb->query($query);

}}
function move_2014(){
        global $years, $logdb, $log, $archive;
        echo "do all sum upc sales by day: <br>";
        $year='2014';
        $months=array(1);
        foreach($months as $month){
                                $month=sprintf("%02d", $month);;
                                $query="INSERT INTO trans_archive`transArchive$year$month`
                                (`datetime`, `register_no`, `emp_no`, `trans_no`, `upc`, `description`, `trans_type`, `trans_subtype`, `trans_status`, `department`, `quantity`, `scale`, `cost`, `unitPrice`, `total`, `regPrice`, `tax`, `foodstamp`, `discount`, `memDiscount`,
`discountable`, `discounttype`, `voided`, `percentDiscount`, `ItemQtty`,
        `volDiscType`, `volume`, `VolSpecial`, `mixMatch`, `matched`, `memType`,
        `staff`, `numflag`, `card_no`, `trans_id`) SELECT
                                `datetime`, `register_no`, `emp_no`, `trans_no`, `upc`, `description`, `trans_type`, `trans_subtype`, `trans_status`, `department`, `quantity`, `Scale`, `cost`, `unitPrice`, `total`, `regPrice`, `tax`, `foodstamp`, `discount`, `memDiscount`,
`discountable`, `discounttype`, `voided`, `percentDiscount`, `ItemQtty`,
 `volDiscType`, `volume`, `VolSpecial`, `mixMatch`, `matched`, `memType`,
         `staff`, `props` `card_no`, `trans_id` FROM is4c_log.`dlog_$year` WHERE MONTH(datetime) = $month";

                        echo $query;
                        echo "<br>";
                #$logdb->query($query);

}}

move_2013();
move_2014();
?>
