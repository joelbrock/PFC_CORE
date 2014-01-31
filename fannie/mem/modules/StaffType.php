<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class StaffType extends MemberModule {

	function ShowEditForm($memNum, $country="US"){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = $dbc->prepare_statement("SELECT c.staff,n.staffType,n.staffDesc,c.discount
				FROM custdata AS c, 
				stafftype AS n 
				WHERE c.CardNo=? AND c.personNum=1
				ORDER BY n.staffType");
		$infoR = $dbc->exec_statement($infoQ,array($memNum));

		$ret = "<fieldset><legend>Staff Type</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";

		$ret .= "<tr><th>Type</th>";
		$ret .= '<td><select name="StaffType_type">';
		$disc = 0;
		while($infoW = $dbc->fetch_row($infoR)){
			$ret .= sprintf("<option value=%d %s>%s</option>",
				$infoW[1],
				($infoW[0]==$infoW[1]?'selected':''),
				$infoW[2]);
			$disc = $infoW[3];
		}
		$ret .= "</select></td>";
		
		$ret .= "<th>Discount</th>";
		
		$ret .= sprintf('<td><input name="MemType_discount" value="%d"
				size="4" /></td></tr>',$disc);	
		
		//$ret .= sprintf('<td>%d%%</td></tr>',$disc);

		$ret .= "</table></fieldset>";
		return $ret;
	}

	function SaveFormData($memNum){
		global $FANNIE_ROOT;
		$dbc = $this->db();
		if (!class_exists("CustdataModel"))
			include($FANNIE_ROOT.'classlib2.0/data/models/CustdataModel.php');

		$mtype = FormLib::get_form_value('StaffType_type',0);

		// Default values for custdata fields that depend on staff Type.
		$CUST_FIELDS = array();
		$CUST_FIELDS['memType'] = ;
		$CUST_FIELDS['Type'] = 'PC';
		$CUST_FIELDS['Staff'] = $mtype;
		$CUST_FIELDS['Discount'] = 0;
		$CUST_FIELDS['SSI'] = 0;

		// Get any special values for this staff Type.
		$q = $dbc->prepare_statement("SELECT discount,staff
			FROM staffdefaults
			WHERE stafftype=?");
		$r = $dbc->exec_statement($q,array($mtype));
		if ($dbc->num_rows($r) > 0){
			$w = $dbc->fetch_row($r);
			$CUST_FIELDS['Discount'] = $w['discount'];
			$CUST_FIELDS['Staff'] = $w['staff'];
		}

		// Assign Member Type values to each custdata record for the Membership.
		$cust = new CustdataModel($dbc);
		$cust->CardNo($memNum);
		$error = "";
		foreach($cust->find() as $obj){
			$obj->memType($mtype);
			$obj->Staff($CUST_FIELDS['Staff']);
			$obj->Discount($CUST_FIELDS['Discount']);
			$upR = $obj->save();
			if ($upR === False)
				$error .= $mtype;
		}
		
		if ($error)
			return "Error: problem saving Staff Type<br />";
		else
			return "";
	}
}

?>
