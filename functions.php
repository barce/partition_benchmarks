<?php


// 
function padNumber ($i_num, $i_max) {

	$s_num = $i_num;
	$s_pad = "";
	$i_diff = 0;
	$i      = 0;

   $i_diff = $i_max - strlen($i_num);

   for ($i = 0; $i < $i_diff; $i++) {
      $s_pad .= "0";
   }

   $s_num = $s_pad . $s_num;

   return $s_num;
}

?>
