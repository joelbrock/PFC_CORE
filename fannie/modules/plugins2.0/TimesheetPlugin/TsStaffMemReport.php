<?php
require_once('../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

$ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

class TsStaffMemReport extends FanniePage {

	protected $auth_classes = array('timesheet_access');

	function preprocess(){
		$this->title = "Timeclock - Staff Member Totals Report";
		$this->header = "Timeclock - Staff Member Totals Report";
		if (!$this->current_user && $_GET['login'] == 1 ){
			$this->login_redirect();
			return False;
		}
		return True;
	}
	
	function javascript_content(){
		ob_start();
		?>
		$(document).ready(function() {
			$(".stripeme tr").mouseover(function() {
				$(this).addClass("over");
			});
			$(".stripeme tr").mouseout(function() {
				$(this).removeClass("over");
			});
			$(".stripeme tr:even").addClass("alt");
		});
		$(document).ready(function(){
		    $('a[title]').qtip();
		});
		<?php
		return ob_get_clean();
	}
	
	function css_content(){
		ob_start();
		?>
		tr.alt td { background:whiteSmoke; }
		tr.over td { background:#CCCCFF; }
		.split { color:white; font-weight:bold; background:#999999; height:10px; }
		table th, table th a {
			font-size: 8px;
			text-transform: uppercase;
		}
		table th {
			font-size: 8px;
			text-transform: uppercase;
		}
		<?php
		return ob_get_clean();
	}

	function body_content(){
		global $ts_db, $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
		include ('./includes/header.html');
		echo '<script type="text/javascript" language="Javascript" src="includes/jquery.qtip.min.js"></script>
			<link type="text/css" rel="stylesheet" href="includes/jquery.qtip.min.css" />';
		//	FULL TIME: Number of hours per week
		$ft = 40;
		echo '<form action="'.$_SERVER['PHP_SELF'].'" method=GET>';
		$stored = ($_COOKIE['timesheet']) ? $_COOKIE['timesheet'] : (FormLib::get_form_value('emp_no',0) != '') ?  FormLib::get_form_value('emp_no',0) : '';
		if ($_SESSION['logged_in'] == True) {
			echo '<p>Name: <select name="emp_no">
			<option value="error">Select staff member</option>' . "\n";
	
			$query = $ts_db->prepare_statement("SELECT FirstName, 
				IF(LastName='','',CONCAT(SUBSTR(LastName,1,1),\".\")), emp_no 
				FROM ".$FANNIE_OP_DB.".employees where EmpActive=1 ORDER BY FirstName ASC");
			$result = $ts_db->exec_statement($query);
			while ($row = $ts_db->fetch_array($result)) {
				echo "<option value=\"$row[2]\">$row[0] $row[1]</option>\n";
			}
			echo '</select>&nbsp;&nbsp;*</p>';
		} 
		else {
			echo "<p>Employee Number*: <input type='text' name='emp_no' value='$stored' size=4 autocomplete='off' /></p>";
		}


		$currentQ = $ts_db->prepare_statement("SELECT periodID 
				FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
				WHERE ".$ts_db->now()." BETWEEN periodStart AND periodEnd");
		$currentR = $ts_db->exec_statement($currentQ);
		list($ID) = $ts_db->fetch_row($currentR);

		$query = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
			date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID 
			FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
			WHERE periodStart < ".$ts_db->now()." ORDER BY periodID DESC");
		$result = $ts_db->exec_statement($query);

		echo '<p>Starting Pay Period: <select name="period">
			<option>Please select a starting pay period.</option>';

		while ($row = $ts_db->fetch_array($result)) {
			echo "<option value=\"" . $row['periodID'] . "\"";
			if ($row['periodID'] == $ID) { echo ' SELECTED';}
			echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
		}

		echo "</select><br />";
		echo '<p>Ending Pay Period: <select name="end">
		    <option value=0>Please select an ending pay period.</option>';
		$result = $ts_db->exec_statement($query);
		while ($row = $ts_db->fetch_array($result)) {
			echo "<option value=\"" . $row['periodID'] . "\"";
			if ($row['periodID'] == $ID) { echo ' SELECTED';}
			echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
		}
		echo '</select><button value="run" name="run">Run</button></p></form>';
		if (FormLib::get_form_value('run','') == 'run') {
	
			$emp_no = FormLib::get_form_value('emp_no',0);
			$namesq = $ts_db->prepare_statement("SELECT * FROM ".$FANNIE_OP_DB.".employees WHERE emp_no=? AND EmpActive=1");
			$namesr = $ts_db->exec_statement($namesq,array($emp_no));
// echo $ts_db->num_rows($namesr);
			if ($ts_db->num_rows($namesr) == 0) {
				echo "<div id='alert'><h1>Error!</h1><p>Incorrect, invalid, or inactive employee number entered.</p>
					<p><a href='".$_SERVER['PHP_SELF']."'>Please try again</a></p></div>";
			} 
			else {
				$name = $ts_db->fetch_row($namesr);

				setcookie("timesheet", $emp_no, time()+60*3);
		
				$periodID = FormLib::get_form_value('period',0);
				$end = FormLib::get_form_value('end',$periodID);
				if ($end == 0) $end = $periodID;

				$query1 = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
					periodID as pid 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
					WHERE periodID = ?");
				$result1 = $ts_db->exec_statement($query1,array($periodID));
				$periodStart = $ts_db->fetch_row($result1);

				$query2 = $ts_db->prepare_statement("SELECT date_format(periodEnd, '%M %D, %Y') as periodEnd, 
					periodID as pid 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
					WHERE periodID = ?");
				$result2 = $ts_db->exec_statement($query2,array($end));
				$periodEnd = $ts_db->fetch_row($result2);
				$p = array();
				for ($i = $periodStart[1]; $i < $periodEnd[1]; $i++) {
					$p[] = $i;
				}

				$firstppP = $ts_db->prepare_statement("SELECT MIN(periodID) 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
					WHERE YEAR(periodStart) = YEAR(".$ts_db->now().")");
				$firstppR = $ts_db->exec_statement($firstppP);
				$firstpp = $ts_db->fetch_row($firstppR);
				$y = array();
				for ($i = $firstpp[0]; $i <= $periodEnd[1]; $i++) {
					$y[] = $i;
				}

				// $sql_incl = "";
				// $sql_excl = "AND emp_no <> 9999";

				echo "<h2>$emp_no &mdash; ".$name['FirstName']." ". $name['LastName']."</h2>";

				// BEGIN TITLE
				// 
				$query1 = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
					periodID as pid, DATE(periodStart) 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
					WHERE periodID = ?");
				$result1 = $ts_db->exec_statement($query1,array($periodID));
				$periodStart = $ts_db->fetch_row($result1);

				$query2 = $ts_db->prepare_statement("SELECT date_format(periodEnd, '%M %D, %Y') as periodEnd, 
					periodID as pid, DATE(periodEnd) 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
					WHERE periodID = ?");
				$result2 = $ts_db->exec_statement($query2,array($end));
				$periodEnd = $ts_db->fetch_row($result2);

				// $periodct = ($end !== $periodID) ? $end - $periodID : 1;
				for ($i = $periodStart[1]; $i <= $periodEnd[1]; $i++) {
					// echo $i;
					$periodct++;
					$p[] = $i;
				}
				echo "<h3>" . $periodStart[0] . " &mdash; " . $periodEnd[0] . "</h3>\n";
				echo "Number of payperiods: " . $periodct . "\n";
				// 
				// END TITLE	
				echo "<br />";

				$areasq = $ts_db->prepare_statement("SELECT ShiftName, ShiftID 
					FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".shifts 
					WHERE visible = 1 ORDER BY ShiftOrder");
				$areasr = $ts_db->exec_statement($areasq);

				$shiftInfo = array();
				echo "<table border='1' cellpadding='5' cellspacing=0 class='stripeme'><thead>\n<tr>
					<th>Week</th>
					<th>date</th>
					<th>Name</th>
					<th>Wage</th>";
				while ($areas = $ts_db->fetch_array($areasr)) {
					echo "<div id='vth'><th>" . substr($areas[0],0,6) . "</th></div>";	// -- TODO vertical align th, static col width
					$shiftInfo[$areas['ShiftID']] = $areas['ShiftName'];
				}
				echo "</th><th>PTO new</th><th>Total</th><th>OT</th></tr></thead>\n<tbody>\n";
		
				$weekQ = $ts_db->prepare_statement("SELECT emp_no, area, tdate, periodID, 
					hours, WEEK(tdate) as week_number 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
					WHERE emp_no = ?
					AND DATE(tdate) >= ? AND DATE(tdate) <= ?
					GROUP BY WEEK(tdate)");
				$weekR = $ts_db->exec_statement($weekQ,array($emp_no,$periodStart[2],$periodEnd[2]));
				$totalP = $ts_db->prepare_statement("SELECT SUM(hours) 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
					WHERE DATE(tdate) = ? AND emp_no = ?");
				$depttotP = $ts_db->prepare_statement("SELECT SUM(t.hours) 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet t 
					WHERE DATE(t.tdate) = ? AND t.emp_no = ? AND t.area = ?");
				$nonPTOtotalP = $ts_db->prepare_statement("SELECT SUM(hours) FROM ".
					$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
					WHERE periodID >= ? AND periodID <= ? AND area <> 31 
					AND emp_no = ?");
				$weekoneP = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2) 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
					INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p 
					ON (p.periodID = t.periodID)
					WHERE t.emp_no = ?
					AND t.periodID = ?
					AND t.area <> 31
					AND t.tdate >= DATE(p.periodStart)
					AND t.tdate < DATE(date_add(p.periodStart, INTERVAL 7 day))");
				$weektwoP = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2)
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
					INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
					ON (p.periodID = t.periodID)
					WHERE t.emp_no = ?
					AND t.periodID = ?
					AND t.area <> 31
					AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) 
					AND t.tdate <= DATE(p.periodEnd)");
				
				$workQ = $ts_db->prepare_statement("SELECT WEEK(tdate),DATE(tdate), emp_no
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
					WHERE DATE(tdate) >= ? AND DATE(tdate) <= ? AND emp_no = ?
					GROUP BY DATE(tdate)");
				$workR = $ts_db->exec_statement($workQ, array($periodStart[2],$periodEnd[2],$emp_no));

				while ($row = $ts_db->fetch_row($workR)) {
					$week_no = $row[0];
					$tdate = $row[1];
					$emp_no = $row[2];
			
					$totalr = $ts_db->exec_statement($totalP,array($tdate,$emp_no));
					$total = $ts_db->fetch_row($totalr);
					$color = ($total[0] > (80 * $periodct)) ? "FF0000" : "000000";
					echo "<tr><td>$week_no</td>";
					echo "<td>".date("m/d",strtotime($tdate))."</td>";
					echo "<td>".ucwords($name['FirstName'])." - " . ucwords(substr($name['FirstName'],0,1)) . ucwords(substr($name['LastName'],0,1)) . "</td><td align='right'>$" . $name['pay_rate'] . "</td>";
					$total0 = (!$total[0]) ? 0 : number_format($total[0],2);


					//
					//	LABOR DEPARTMENT TOTALS
					foreach($shiftInfo as $area => $shiftName){	
						// echo $depttotq;
						$depttotr = $ts_db->exec_statement($depttotP,array($tdate,$emp_no,$area));
						$depttot = $ts_db->fetch_row($depttotr);
						$depttotal = (!$depttot[0]) ? 0 : number_format($depttot[0],2);
						// echo "<td align='right'>" . $depttotal . "</td>";
				
						if ($area == 32) {
							$commQ = $ts_db->prepare_statement("SELECT comment FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
								WHERE tdate = ? AND emp_no = ? AND area = 32 AND comment <> ''");
							$commR = $ts_db->exec_statement($commQ, array(
								$tdate, $emp_no
							));
							$comment = $ts_db->fetch_row($commR);
							$otag = ($comment[0]!="")? "<a title='".$comment[0]."'>" : "";
							$ctag = "</a>";
						}
						echo "<td align='right'>".$otag.$depttotal.$ctag."</td>";

						$otag = "";
						$ctag = "";
					}
					//	END LABOR DEPT. TOTALS


					//	TOTALS column
					// echo "<td align='right'><font style='color: $color; font-weight:bold;'>" . $total0 . "</font></td>";

					//
					//	PTO CALC
					$nonPTOtotalr = $ts_db->exec_statement($nonPTOtotalP,array($periodID,$end,$emp_no));
					$nonPTOtotal = $ts_db->fetch_row($nonPTOtotalr);
					$ptoAcc = ($name['JobTitle'] == 'STAFF') ? $total[0] * 0.075 : 0;
					echo "<td align='right'>" . number_format($ptoAcc,2) . "</td>";

					echo "<td align='right'><font style='color: $color; font-weight:bold;'>" . $total0 . "</font></td>";

					// 
					//	OVERTIME
					$otQ = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2) 
						FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
				        INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p 
						ON (p.periodID = t.periodID)
				        WHERE t.emp_no = ? AND t.periodID = ? AND t.area <> 31
						AND t.tdate BETWEEN STR_TO_DATE(CONCAT(YEAR(?),WEEK(?),' Sunday'), '%X%V %W') 
						AND ?");
					// echo $otQ;
					$otR = $ts_db->exec_statement($otQ, array($emp_no,$periodID,$tdate,$tdate,$tdate));

					list($ot_day) = $ts_db->fetch_row($otR);
					if (is_null($ot_day)) $ot_day = 0;
					$otime = (($ot_day - $ft) > 0) ? $ot_day - $ft - $ot_yest : 0;

					echo "<td align='right'>" . $otime . "</td>";

					$ot_yest = $otime; 
					$OT[] = $otime;
					// 	END OVERTIME
					echo "</tr>";

				}
				
				echo "<tr><td colspan=4><b>TOTALS</b></td>";

				$TOT = array();
				$areasq = $ts_db->prepare_statement("SELECT ShiftName, ShiftID 
					FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".shifts 
					WHERE visible = 1 ORDER BY ShiftOrder");
				$areasr = $ts_db->exec_statement($areasq);
				while ($areas = $ts_db->fetch_array($areasr)) {
					$query = $ts_db->prepare_statement("SELECT ROUND(SUM(hours),2) 
						FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet t 
						WHERE emp_no = ? AND tdate BETWEEN ? AND ? AND area = ?");
					$totsr = $ts_db->exec_statement($query, array(
						$emp_no, $periodStart[2], $periodEnd[2], $areas[1]
					));
					$tots = $ts_db->fetch_row($totsr);
					$tot = (!$tots[0] || $tots[0] == '') ? '0' : $tots[0];
					echo "<td align='right'><b>$tot</b></td>";
					$TOT[] = $tot;
				}

				$PTOTOT = number_format(array_sum($PTOnew),2);
				echo "<td><b>$PTOTOT</b></td>";

				$TOTAL = number_format(array_sum($TOT),2);
				echo "<td><b>$TOTAL</b></td>";

				$OTTOT = number_format(array_sum($OT),2);
				echo "<td><b>$OTTOT</b></td>";

				echo"</tr>";
				
				echo "</tbody></table>\n";
			}
	
		} // end 'run' button 

		// if ($this->current_user){
		// 	echo "<div class='log_btn'><a href='" . $FANNIE_URL . "auth/ui/loginform.php?logout=1'>logout</a></div>";
		// } else {
		// 	echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?login=1'>login</a></div>";  //   class='loginbox'
		// }
	}


}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new TsStaffMemReport();
	$obj->draw_page();
}

?>
