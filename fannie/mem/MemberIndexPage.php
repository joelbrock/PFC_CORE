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
include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class MemberIndexPage extends FanniePage {

	protected $title = "Fannie :: Member Tools";
	protected $header = "Member Tools";

	function body_content(){
		ob_start();
		?>
		<ul>
		<li><a href="MemberSearchPage.php">View/Edit Members</a></li>
		<li><a href="MemberTypeEditor.php">Manage Member Types</a></li>
		<li><a href="NewMemberTool.php">Create New Members</a></li>
		<li><a href="numbers/index.php">Print Member Stickers</a></li>
		<li><a href="import/">Import Data</a></li>
		</ul>
		<?php
		return ob_get_clean();
	}
}

FannieDispatch::conditionalExec(false);

?>
