<?php
$current_dir = dirname(__FILE__);
$prefix = "crontest-";
$now = date("Y-m-d.H:i:s");
touch($current_dir . DIRECTORY_SEPARATOR . $prefix . $now);
?>