<?php
$dbhost = 'localhost:3036';
$dbuser = 'root';
$dbpass = 'behance';
$conn = mysql_connect($dbhost, $dbuser, $dbpass);
if(! $conn )
{
  die('Could not connect: ' . mysql_error());
}
echo 'Connected successfully<br />';
$sql = "CREATE TABLE users( ".
       "id INT NOT NULL AUTO_INCREMENT, ".
       "email VARCHAR(100) NOT NULL DEFAULT '', ".
       "fname VARCHAR(50) NOT NULL DEFAULT '', ".
       "lname VARCHAR(50) NOT NULL DEFAULT '', ".
       "roles VARCHAR(50) NOT NULL DEFAULT '', ".
       "PRIMARY KEY ( id ), ".
       "UNIQUE KEY unique_email ( email ) ".
       ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
mysql_select_db( 'fauxgram' );
$retval = mysql_query( $sql, $conn );
if(! $retval )
{
  die('Could not create table: ' . mysql_error());
}
echo "Table created successfully\n";
mysql_close($conn);
?>