<?php
	$HOST = 'mysql.railway.internal';      // або $_ENV['MYSQLHOST']
	$USER = 'root';      // або $_ENV['MYSQLUSER']
	$PASS = 'aXGnEnpUJZUgJHBZTNSOlpisswEMAtCr';  // або $_ENV['MYSQLPASSWORD']
	$DB   = 'railway';  // або $_ENV['MYSQLDATABASE']
	$PORT = '3306';      // або $_ENV['MYSQLPORT']

	$conn = mysqli_connect($HOST, $USER, $PASS, $DB, $PORT)
		or die("Connection error: " . mysqli_connect_error());
	mysqli_set_charset($conn, "utf8");
?>