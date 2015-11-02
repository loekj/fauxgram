<?php
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = 'behance';
$db = 'fauxgram';
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $db);

if( $mysqli->connect_errno ) {
  die('Could not connect: ' . $mysqli->connect_error);
}
$sql = "CREATE TABLE users( ".
       "id INT NOT NULL AUTO_INCREMENT, ".
       "email VARCHAR(100) NOT NULL DEFAULT '', ".
       "password VARCHAR(255) NOT NULL DEFAULT '', ".
       "fname VARCHAR(50) NOT NULL DEFAULT '', ".
       "lname VARCHAR(50) NOT NULL DEFAULT '', ".
       "PRIMARY KEY ( id ), ".
       "UNIQUE KEY unique_email ( email ) ".
       ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if(! $mysqli->query($sql)) {
  die('Could not create table. Error: ' . $mysqli->errno);
}

echo "Table users created successfully\n";
$mysqli->close();
?>