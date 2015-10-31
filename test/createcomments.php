<?php
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = 'behance';
$db = 'fauxgram';
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $db);

if( $mysqli->connect_errno ) {
  die('Could not connect: ' . $mysqli->connect_error);
}
$sql = "CREATE TABLE comments( ".
       "id INT NOT NULL AUTO_INCREMENT, ".
       "owner VARCHAR(100) NOT NULL DEFAULT '', ".
       "added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ".
       "content TINYBLOB NOT NULL, ".
       "PRIMARY KEY ( id )".
       ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if(! $mysqli->query($sql)) {
  die('Could not create table. Error: ' . $mysqli->errno);
}

echo "Table comments created successfully\n";
$mysqli->close();
?>