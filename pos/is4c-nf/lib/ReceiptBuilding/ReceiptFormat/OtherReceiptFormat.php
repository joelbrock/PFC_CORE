<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op.

    This file is part of IT CORE.

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

/**
  @class OtherFormat
  Module for print-formatting 
  miscelaneous records. 
*/
class OtherReceiptFormat extends DefaultReceiptFormat 
{

	/**
	  Formatting function
	  @param $row a single receipt record
	  @return a formatted string
	*/
	public function format($row)
    {
		if ($row['trans_type'] == '0') {
			// tare
			$description = strtolower($description);
			$description = str_replace('**',' =',$description);
			return $description;
		} else if ($row['trans_type'] == 'H' && $row['description'] != '') {
			$this->is_bold = True;
			return $row['description'];
		}
		return "";
	}
}

