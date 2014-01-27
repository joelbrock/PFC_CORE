<?php
global $CORE_LOCAL;
include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

$page_title = 'Fannie - Reporting';
$header = 'Item Properties Report';
include($FANNIE_ROOT.'src/header.html');

if ($_REQUEST['submit'] == "submit") {
	$dbc = FannieDB::get($FANNIE_OP_DB);
	echo '<script type="text/javascript" language="Javascript" src="http://code.highcharts.com/highcharts.js"></script>';
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}
	$d1 = $_REQUEST['date1'];
	$d2 = $_REQUEST['date2'];
	// $dept = $_REQUEST['dept'];  // TODO:  add a dept/superdept filter to search form
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
	$grossQ = $dbc->prepare_statement("SELECT ROUND(sum(total),2) as GROSS_sales
		FROM $dlog WHERE DATE(datetime) BETWEEN ? AND ?
		AND department BETWEEN 1 AND 20
		AND trans_type <> 'T'");
	$grossR = $dbc->exec_statement($grossQ,array($d1, $d2));
	$grossW = $dbc->fetch_row($grossR);
	$gross = ($grossW[0]) ? $grossW[0] : 0;

	// echo "<div id='progressbar'></div>";	
	
	echo "<div id='chart' style='width:100%; height: 300px;'>"; 
	echo "</div>";	
	echo "\n<h3 style='text-align:center;'>GROSS TOTAL FOR $d1 thru $d2:  <b>" . money_format('%n', $gross) . "</b></h3>\n";
	
	$itemsQ = $dbc->prepare_statement("SELECT COUNT(DISTINCT p.upc) as itmct,
			i.description as Item_Property, 
			COUNT(p.numflag) as Count,
			ROUND(SUM(d.total),2) as Sales,
			ROUND((SUM(d.total)/$gross)*100,2) as pct_of_gross,
			i.bit_number as id
		FROM products p, prodFlags i, $dlog d
		WHERE DATE(d.datetime) BETWEEN ? AND ?
		AND p.numflag >= 1
		AND p.upc = d.upc
		AND BINARY(p.numflag) & i.bit_number 
		GROUP BY Item_Property");
	$itemsR = $dbc->exec_statement($itemsQ, array($d1, $d2));
	if (!$itemsR) { die("Query: $itemsQ<br />Error:".mysql_error()); }
	
	echo "<table align=center id='output' cellpadding=6 cellspacing=0 border=0 class=\"sortable-onload-3 rowstyle-alt colstyle-alt\">\n
	  <thead>\n
	    <tr>\n
	      <th class=\"sortable-text\">Item Property (ct.)</th>\n
	      <th class=\"sortable-numeric favour-reverse\">Rings</th>\n
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

} else {	
		?>
		<script type="text/javascript" src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"></script>
		<form action="ItemPropertiesReport.php" method="POST">
		<table><tr>
		<td>Date Start:</td>
		<td><div class="date"><p><input type="text" name="date1" onfocus="this.value='';showCalendarControl(this);" />&nbsp;&nbsp;*</p></div></td>
		</tr><tr>
		<td>Date End:</td>
		<td><div class="date"><p><input type="text" name="date2" onfocus="this.value='';showCalendarControl(this);" />&nbsp;&nbsp;*</p></div></td>
		</tr>
		<tr></tr>
		</table>
		<input type=submit name=submit value=submit></input>
		<div style="float:right;"><?php echo FormLib::date_range_picker(); ?></div>
		</form>
		<?php

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
                ['Local',	<?=$local; ?>],
                ['Non-Local',	<?=100-$local; ?>]
            ]
        }]
    });
});
</script>
<!--<script>
	$(function() {
		$( ".datepicker" ).datepicker({ 
			dateFormat: 'yy-mm-dd' 
		});
	});
</script>
-->
