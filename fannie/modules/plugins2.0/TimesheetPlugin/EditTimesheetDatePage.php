<?php
require_once(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

$ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

class EditTimesheetDatePage extends FanniePage {
	
	private $errors;
	private $display_func;

	function javascript_content(){
		ob_start();
		?>
		function switchType(type,n){
		  if(type=='32'){
		    $('.other'+n).slideDown("slow");
		  } else {
		    $('.other'+n).hide("slow");
		  }
		};
		<?php
		return ob_get_clean();
	}

	function preprocess(){
		global $ts_db, $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;

		$max = 10; // Max number of entries.

		$this->header = 'Timesheet Management';
		$this->title = 'Fannie - Administration Module';
		$this->errors = array();
		$this->display_func = '';	

		$submit = FormLib::get_form_value('submit','');
		$submitted = FormLib::get_form_value('submitted','');
		$emp_no = FormLib::get_form_value('emp_no','');
		$date = FormLib::get_form_value('date','');
		$periodID = FormLib::get_form_value('periodID','');
		if (empty($submitted) && empty($emp_no)){
			$this->errors[] = 'You have found this page mistakenly.';
		}
		elseif (isset($_POST['submitted'])) { // If the form has been submitted.
			if ($_POST['submit'] == 'delete') {
				$query = $ts_db->prepare_statement("DELETE 
					FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
					WHERE emp_no=? AND tdate=?");
				$result = $ts_db->exec_statement($query,array($emp_no,$date));
				if ($result) {
					$this->display_func = 'ts_delete_msg';
				} 
				else {
					$this->errors[] = 'The day could not be removed, please try again later.';
				}
			} 
			elseif ($_POST['submit'] == 'submit') {

				// Validate the data.
				$entrycount = 0;
				for ($i = 1; $i <= $max; $i++) {
					if ((isset($_POST['hours' . $i])) && (is_numeric($_POST['area' . $i]))) {
						$entrycount++;
					}
				}

				$hours = array();
				$area = array();

				if ($entrycount == 0) {
					$this->errors[] = 'You didn\'t enter any hours.';
				} 
				else {
					for ($i = 1; $i <= $entrycount; $i++) {
						if (((!$_POST['hours' . $i]) || (!$_POST['area' . $i])) && $_POST['hours' . $i] != 0) 
							$this->errors[] = "For entry $i: Either the Hours or the Labor Category were not set.";
					}
					for ($i = 1; $i <= $max; $i++) {
						if ((isset($_POST['hours' . $i])) && (is_numeric($_POST['area' . $i]))) {
							$hours[$i] = $_POST['hours' . $i];
							$area[$i] = $_POST['area' . $i];
							$ID[$i] = $_POST['ID' . $i];
							$comment[$i] = $_POST['comment' . $i];
						}
					}
				}
                 
				if (empty($errors)) { // All good.

					$successcount = 0;
					$upP = $ts_db->prepare_statement("UPDATE 
						{$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
						SET hours=?,area=?,comment=?
					    WHERE emp_no=? AND tdate=? AND ID=?");

					$insP = $ts_db->prepare_statement("INSERT INTO 
						{$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
						(emp_no, hours, area, tdate, periodID, wage, comment) VALUES (?,?,?,?,?,?,?)");
					for ($i = 1; $i <= $entrycount; $i++) {
						if (is_numeric($ID[$i])) {
							$result = $ts_db->exec_statement($upP,array(
								$hours[$i],$area[$i],$comment[$i],
								$emp_no, $date, $ID[$i]
							));
							if ($result) {$successcount++;} 
							else {
								$this->errors[] = 'Query: ' . $query;
								$this->errors[] = 'MySQL Error: ' . $ts_db->error();
							}
						} 
						elseif ($ID[$i] == 'insert') {
							$wageQ = $ts_db->prepare_statement("SELECT pay_rate FROM {$FANNIE_OP_DB}.employees where emp_no=?");
							$wageR = $ts_db->exec_statement($wageQ,array($emp_no)) OR DIE (mysql_error());
							$wage = $ts_db->fetch_row($wageR);				
							$result = $ts_db->exec_statement($insP,array(
								$emp_no, $hours[$i],
								$area[$i], $date, $periodID, $wage[0], $comment[$i]
							));
							
							if ($result) {$successcount++;} 
							else {
								$this->errors[] = 'Query: ' . $query;
								$this->errors[] = 'MySQL Error: ' . $ts_db->error();
							}
						}
					}
                
					if ($successcount == $entrycount) {
						// Start the redirect.
						$url = "ViewsheetPage.php?emp_no=$emp_no&period=$periodID";
						header("Location: $url");
						return False;
					} 
					else {
						$this->errors[] = 'The entered hours could not be updated, Unknown error.';
						$this->errors[] = 'Error: ' . $ts_db->error();
						$this->errors[] = '<p>Query: ' . $query;
					}
	    
				} 
			}
		}
		else if (!empty($periodID)){
			// Make sure we're in a valid pay period.       
			$query = $ts_db->prepare_statement("SELECT DATEDIFF(CURDATE(), DATE(periodEnd)) 
				FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
				WHERE periodID = ?");
			$result = $ts_db->exec_statement($query,array($periodID));
			list($datediff) = $ts_db->fetch_row($result);

			if ($datediff > 1) { // Bad.
				$this->errors[] = "You can't edit hours more than a day after the pay period has ended.";
			}
		}
		
		if (!empty($this->errors)){
			$this->display_func = 'ts_error';
		}

		return True;
	}

	function delete_msg(){
		// include ('./includes/header.html');
		echo '<div class="alert"><p>The day has been removed from your timesheet.</p></div>';
	}

	function error_content(){
		// include ('./includes/header.html');
		echo '<p><font color="red">The following error(s) occurred:</font></p>';
		foreach ($this->errors AS $message) {
			echo "<p> - $message</p>";
		}
		echo '<form><p><a onclick="window.history.back()" style="cursor:pointer;">Please try again.</a></p></form>';
	}
        
	function body_content(){
		global $ts_db, $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
		$max = 10; // Max number of entries.
		include ('./includes/header.html');
		if ($this->display_func == 'ts_error')
			return $this->error_content();
		elseif ($this->display_func == 'ts_delete_msg')
			return $this->delete_msg();

		$emp_no = FormLib::get_form_value('emp_no','');
		$date = substr(FormLib::get_form_value('date',''),0,10);
		$periodID = FormLib::get_form_value('periodID','');

		$query = $ts_db->prepare_statement("SELECT CONCAT(FirstName,' ',LastName) 
				FROM {$FANNIE_OP_DB}.employees where emp_no=?");
		$result = $ts_db->exec_statement($query,array($emp_no));
		// echo $query;
		list($name) = $ts_db->fetch_row($result);
		echo "<form action='".$_SERVER['PHP_SELF']."' method='POST'>
			<input type='hidden' name='emp_no' value='$emp_no' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='submitted' value='TRUE' />
			<p align='center'><button name='submit' type='submit' value='delete'>Remove this day from my timesheet.</button></p>
			</form>";

		echo "<form action='".$_SERVER['PHP_SELF']."' method='POST'>";
		echo "<table border=0 cellpadding=4><tr><td><p>Name: <strong>$name</strong></p></td><td><p>Date: <strong>". substr($date, 0, 4) . "-" . substr($date, 5, 2) . "-" . substr($date, 8, 2) . "</strong></p></td></tr>
			<input type='hidden' name='emp_no' value='$emp_no' />
			<input type='hidden' name='periodID' value='$periodID' />               
			<input type='hidden' name='date' value='$date' />";

		echo "<tr><td align='right'><b>Total Hours</b></td><td align='center'><strong>Labor Category</strong></td>
			<!--<td><strong>Remove</strong></td>--></tr>\n";
			
		for ($i = 1; $i <= $max; $i++) {
			$inc = $i - 1;
			$query1 = $ts_db->prepare_statement("SELECT hours, area, ID, comment 
				FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
				WHERE emp_no = ? AND DATE(tdate) = ? ORDER BY ID ASC LIMIT ?,1");
			$result1 = $ts_db->exec_statement($query1,array($emp_no,$date,$inc)) OR DIE (mysql_error());
			$num = $ts_db->num_rows($result1);		
			if ($row = $ts_db->fetch_row($result1)) {
				$hours = ($row[0])?$row[0]:'';
				$area = $row[1];
				$ID = $row[2];
				$comment = $row[3];
			} else {
				$hours = '';
				$area = NULL;
				$ID = "insert";
			}
			echo "<tr><td align='right'><input type='text' name='hours" . $i . "' value='$hours' size=6></input></td>";
			$query2 = $ts_db->prepare_statement("SELECT IF(NiceName='', ShiftName, NiceName), ShiftID 
				FROM " . $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'] . ".shifts 
				WHERE visible=true ORDER BY ShiftOrder ASC");
			$result2 = $ts_db->exec_statement($query2);
			echo '<td><select name="area' . $i . '" id="area' . $i . '" onchange="switchType(this.value,'.$i.')"><option>Please select an area of work.</option>';
			while ($row = $ts_db->fetch_row($result2)) {
				echo "<option id =\"$i$row[1]\" value=\"$row[1]\" ";
				if ($row[1] == $area) echo "SELECTED";
				echo ">$row[0]</option>";
			}
			echo "</select><input type='hidden' name='ID" . $i . "' value='$ID' /></td>";
			echo "</tr>\n";
			if ($area == 32)
				echo "<tr class='other".$i."'><td colspan=2 align='right' valign='top'><textarea name='comment".$i."' cols=38 rows=2>$comment</textarea></td></tr>";
			echo "<tr class='other".$i."' style='display:none'><td colspan=2 align='right' valign='top'>Explain*:<textarea name='comment".$i."' cols=38 rows=2></textarea></td></tr>";			
		}
		echo '<tr><td colspan=2 align="center"><button name="submit" type="submit" value="submit"';
		// echo "onclick='confirm('Do you really want to DELETE hours?')' ";
		echo'>Submit</button>
			<input type="hidden" name="submitted" value="TRUE" /></td></tr>';
		echo '</table></form>';
	}
}

FannieDispatch::conditionalExec(false);

?>
