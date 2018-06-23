<?php

PHP_SAPI === 'cli' or die('not allowed');

if (sizeof($argv) != 2) {
	print("exactly one argument is required");
	exit();
}

require_once('../code/config.php');
$db_handle = mysqli_connect($db_server,$db_user,$db_pwd,$db_name);

if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$result = mysqli_query($db_handle, "SELECT gruppenmitglieder.email FROM gruppenmitglieder WHERE diensteinteilung='" . $argv[1] . "'");

foreach($result as $r) {
	$email = trim($r['email']);
	print($email . "\n");
}

mysqli_close($db_handle);
?>
