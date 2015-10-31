<?php
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = 'behance';
$db = 'fauxgram';
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $db);

if( $mysqli->connect_errno ) {
  die('Could not connect: ' . $mysqli->connect_error);
}
$sql = "CREATE TABLE images( ".
       "id INT NOT NULL AUTO_INCREMENT, ".
       "owner VARCHAR(100) NOT NULL DEFAULT '', ".
       "added TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ".
       "path VARCHAR(100) NOT NULL DEFAULT '', ".
       "title VARCHAR(50) NOT NULL DEFAULT '', ".
       "ext VARCHAR(50) NOT NULL DEFAULT '', ".
       "bytes INT NOT NULL DEFAULT 0, ".
       "size VARCHAR(50) NOT NULL DEFAULT '', ".
       "PRIMARY KEY ( id )".
       ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if(! $mysqli->query($sql)) {
  die('Could not create table. Error: ' . $mysqli->errno);
}

echo "Table images created successfully\n";
$mysqli->close();
?>