<?php
	$HOST = getenv('MYSQLHOST');      // або $_ENV['MYSQLHOST']
	$USER = getenv('MYSQLUSER');      // або $_ENV['MYSQLUSER']
	$PASS = getenv('MYSQLPASSWORD');  // або $_ENV['MYSQLPASSWORD']
	$DB   = getenv('MYSQLDATABASE');  // або $_ENV['MYSQLDATABASE']
	$PORT = getenv('MYSQLPORT');      // або $_ENV['MYSQLPORT']

	$conn = mysqli_connect($HOST, $USER, $PASS, $DB, $PORT)
		or die("Connection error: " . mysqli_connect_error());
	mysqli_set_charset($conn, "utf8");
?>