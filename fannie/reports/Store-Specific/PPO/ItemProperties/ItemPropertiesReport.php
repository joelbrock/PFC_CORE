<?php

include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

$page_title = 'Fannie - Reporting';
$header = 'Item Properties Report';
class ItemPropertiesReport extends FannieReportPage
{
    protected $title = "Fannie : Item Properties";
    protected $header = "Item Properties Report";
    protected $report_headers = array('Date','UPC','Description','Qty','$');
    protected $required_fields = array('year1', 'year2');


	function fetch_report_data() {


//if ($_REQUEST['submit']) {
//	require_once '../../../../config.conf';
//	include '../src/functions.php';
//	include 'reportFunctions.php';
//	include '../../../../src/header.php';
	echo '<script type="text/javascript" language="Javascript" src="http://code.highcharts.com/highcharts.js"></script>';
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}
	// Check year in query, match to a dlog table
	$year1 = idate('Y',strtotime($date1));
	$year2 = idate('Y',strtotime($date2));

	// echo "<head>\n";
	// include '../src/head.php';
	// echo "\n</head>\n\n";
	if ($year1 != $year2) {
		echo "<div id='alert'><h4>Reporting Error</h4><p>Fannie cannot run reports across multiple years.<br>Please retry your query.</p></div>\n";
	} else { $table = 'dlog_' . $year1; }
	$gross = gross($table,$date1,$date2);
		
	// echo "<div id='progressbar'></div>";	
	
	echo "<div id='chart' style='width:100%; height: 300px;'>"; 
	echo "</div>";	
	echo "\n<p>GROSS TOTAL FOR $date1 thru $date2:  <b>" . money_format('%n', $gross) . "</b></p>\n";
	
	$propR = mysql_query("SELECT * FROM item_properties");
	
	$itemsQ = "SELECT COUNT(DISTINCT p.upc) as itmct,
			i.name as Item_Property, 
			COUNT(p.props) as Count,
			ROUND(SUM(d.total),2) as Sales,
			ROUND((SUM(d.total)/$gross)*100,2) as pct_of_gross,
			i.bit as id
		FROM " . PRODUCTS_TBL . " p, item_properties i, " . DB_LOGNAME . ".$table d
		WHERE DATE(d.datetime) BETWEEN '".$date1."' AND '".$date2."' 
		AND p.props >= 1
		AND p.upc = d.upc
		AND BINARY(p.props) & i.bit 
		GROUP BY Item_Property";
	$itemsR = mysql_query($itemsQ);
	if (!$itemsR) { die("Query: $itemsQ<br />Error:".mysql_error()); }
	
	echo "<table id='output' cellpadding=6 cellspacing=0 border=0 class=\"sortable-onload-3 rowstyle-alt colstyle-alt\">\n
	  <thead>\n
	    <tr>\n
	      <th class=\"sortable-text\">Item Property (ct.)</th>\n
	      <th class=\"sortable-numeric favour-reverse\">Count.</th>\n
	      <th class=\"sortable-currency favour-reverse\">Sales</th>\n
	      <th class=\"sortable-numeric favour-reverse\">% of gross</th>\n
	    </tr>\n
	  </thead>\n
	  <tbody>\n";
	$local = 0;
	while ($row = mysql_fetch_assoc($itemsR)) {
		echo "<td align=left><b>" . $row['Item_Property'] . "</b> (" . $row['itmct'] . ")</td>\n
			<td align=right>" . $row['Count'] . "</td>\n
			<td align=right>" . money_format('%n',$row['Sales']) . "</td>\n
			<td align=right>" . number_format($row['pct_of_gross'],2) . "%</td>\n";
		echo "</tr>\n";
		if ($row['id'] == 1 || $row['id'] == 512) 
			$local += $row['pct_of_gross'];
	}
	echo "</tbody></table>\n";
	
	// debug_p($_REQUEST, "all the data coming in");
	
	//include '../src/footer.php'; 	
	}
//} else {

//	include '../../../../src/header.php';
	function form_content() {
		echo "<form action=\"itemProperties.php\" method=\"POST\" target=\"_blank\">\n
		<table>\n<tr>
		<td>Date Start:</td>\n
		<td><div class=\"date\"><p><input type=\"text\" name=\"date1\" class=\"datepicker\" />&nbsp;&nbsp;*</p></div></td>\n
		</tr>\n<tr>
		<td>Date End:</td>\n
		<td><div class=\"date\"><p><input type=\"text\" name=\"date2\" class=\"datepicker\" />&nbsp;&nbsp;*</p></div></td>\n
		</tr>\n<tr></tr>
		
		</table>
		<input type=submit name=submit value=submit></input></form>";



		include $FANNIE_ROOT.'src/footer.php'; 
	}
//}
}

?>
<script>
$(function () {
    $('#chart').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false
        },
        title: {
            text: 'Comparison of Local products vs. Non-Local'
        },
        tooltip: {
    	    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    color: '#000000',
                    connectorColor: '#000000',
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Local Products',
            data: [
                ['Local',		<?=$local; ?>],
                ['Non-Local',	<?=100-$local; ?>]
            ]
        }]
    });
});
</script>
<script>
	$(function() {
		$( ".datepicker" ).datepicker({ 
			dateFormat: 'yy-mm-dd' 
		});
	});
</script>
