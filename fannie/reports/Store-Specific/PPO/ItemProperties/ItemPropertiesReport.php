<?php

include($FANNIE_ROOT.'config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

$page_title = 'Fannie - Reporting';
$header = 'Item Properties Report';
include($FANNIE_ROOT.'src/header.html');

    // protected $title = "Fannie : Item Properties";
    // protected $header = "Item Properties Report";
    // protected $report_headers = array('Date','UPC','Description','Qty','$');
    // protected $required_fields = array('year1', 'year2');

if ($_REQUEST['submit']) {
//	require_once '../../../../config.conf';
//	include '../src/functions.php';
//	include 'reportFunctions.php';
	$dbc = FannieDB::get($FANNIE_OP_DB);
	include($FANNIE_ROOT.'src/header.html');
	echo '<script type="text/javascript" language="Javascript" src="http://code.highcharts.com/highcharts.js"></script>';
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}
	// Check year in query, match to a dlog table
	$d1 = $_REQUEST['date1'];
	$d2 = $_REQUEST['date2'];
	// $dept = $_REQUEST['dept'];
	if ( isset($_REQUEST['other_dates']) ) {
		switch ($_REQUEST['other_dates']) {
			case 'today':
				$d1 = date("Y-m-d");
				$d2 = $d1;
				break;
			case 'yesterday':
				$d1 = date("Y-m-d", strtotime('yesterday'));
				$d2 = $d1;
				break;
			case 'this_week':
				$d1 = date("Y-m-d", strtotime('last monday'));
				$d2 = date("Y-m-d");
				break;
			case 'last_week':
				$d1 = date("Y-m-d", strtotime('last monday - 7 days'));
				$d2 = date("Y-m-d", strtotime('last sunday'));
				break;
			case 'this_month':
				$d1 = date("Y-m-d", strtotime('first day of this month'));
				$d2 = date("Y-m-d");
				break;
			case 'last_month':
				$d1 = date("Y-m-d", strtotime('first day of last month'));
				$d2 = date("Y-m-d", strtotime('last day of last month'));
				break;
		}
	}
	$dlog = DTransactionsModel::selectDtrans($d1,$d2);
	// $gross = gross($table,$date1,$date2);
	$gross = 0; // FOR TESTING
	// echo "<div id='progressbar'></div>";	
	
	echo "<div id='chart' style='width:100%; height: 300px;'>"; 
	echo "</div>";	
	echo "\n<p>GROSS TOTAL FOR $d1 thru $d2:  <b>" . money_format('%n', $gross) . "</b></p>\n";
	
	$propR = mysql_query("SELECT * FROM item_properties");
	
	$itemsQ = $dbc->prepare_statement("SELECT COUNT(DISTINCT p.upc) as itmct,
			i.description as Item_Property, 
			COUNT(p.props) as Count,
			ROUND(SUM(d.total),2) as Sales,
			ROUND((SUM(d.total)/$gross)*100,2) as pct_of_gross,
			i.bit_number as id
		FROM products p, prodFlags i, $dlog d
		WHERE DATE(d.datetime) BETWEEN ? AND ?
		AND p.props >= 1
		AND p.upc = d.upc
		AND BINARY(p.props) & i.bit_number 
		GROUP BY Item_Property");
	$itemsR = $dbc->exec_statement($itemsQ, $d1, $d2);
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
	while ($row = $dbc->fetch_row($itemsR)) {
		echo "<td align=left><b>" . $row['Item_Property'] . "</b> (" . $row['itmct'] . ")</td>\n
			<td align=right>" . $row['Count'] . "</td>\n
			<td align=right>" . money_format('%n',$row['Sales']) . "</td>\n
			<td align=right>" . number_format($row['pct_of_gross'],2) . "%</td>\n";
		echo "</tr>\n";
		if ($row['id'] == 1 || $row['id'] == 512) 
			$local += $row['pct_of_gross'];
	}
	echo "</tbody></table>\n";

	}
} else {	
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


}

include $FANNIE_ROOT.'src/footer.html'; 

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
