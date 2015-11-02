<?php
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = 'behance';
$db = 'fauxgram';
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $db);

if( $mysqli->connect_errno ) {
  die('Could not connect: ' . $mysqli->connect_error);
}

$sql = "DROP TABLE users;";
if(! $mysqli->query($sql)) {
  die('Could not drop table. Error: ' . $mysqli->errno);
}

echo "Table users dropped successfully\n";
$mysqli->close();
?>