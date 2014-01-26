<?php

include('../lib/AutoLoader.php');
echo "<h3>Current PHP \$_SESSION Variables</h3>";
foreach($_SESSION as $key => $val) {
	echo $key.": ";
	if(is_array($val) && isset($val)){
		print_r($val);
		echo "<br />";
	} else {
		echo $val."<br />";
	}
}

?>

