<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("Parser")) include_once($IS4C_PATH."parser-class-lib/Parser.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class QuickKeyLauncher extends Parser {
	
	function check($str){
		if (strstr($str,"QK")){
			$tmp = explode("QK",$str);
			$ct = count($tmp);
			if ($ct <= 2 && is_numeric($tmp[$ct-1]))
				return True;
		}
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL,$IS4C_PATH;
		$tmp = explode("QK",$str);
		if (count($tmp) == 2)
			$IS4C_LOCAL->set("qkInput",$tmp[0]);
		else
			$IS4C_LOCAL->set("qkInput","");
		$IS4C_LOCAL->set("qkNumber",$tmp[count($tmp)-1]);
		$IS4C_LOCAL->set("qkCurrentId",$IS4C_LOCAL->get("currentid"));
		$ret = $this->default_json();
		$ret['main_frame'] = $IS4C_PATH.'gui-modules/QKDisplay.php';
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>anything</i>QK<i>number</i></td>
				<td>
				Go to quick key with the given number.
				Save any provided input.
				</td>
			</tr>
			</table>";
	}
}

?>