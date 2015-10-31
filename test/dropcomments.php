<?php
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = 'behance';
$db = 'fauxgram';
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $db);

if( $mysqli->connect_errno ) {
  die('Could not connect: ' . $mysqli->connect_error);
}

$sql = "DROP TABLE comments;";
if(! $mysqli->query($sql)) {
  die('Could not drop table. Error: ' . $mysqli->errno);
}

echo "Table comments dropped successfully\n";
$mysqli->close();
?>