<?php
$dsn = 'mysql:dbname=fauxgram;host=127.0.0.1';
$db = new PDO($dsn, 'root', 'behance');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function hasher($pwd) {
	return password_hash($pwd,PASSWORD_BCRYPT, array('cost' => 10));
}

$user_insert = "INSERT INTO users (email, fname, lname, password) VALUES ";
$users = array(
	"'ljanssen@stanford.edu', 'Loek', 'Janssen'",
	"'dummy@dummy.com', 'Dummy', 'Dummy'",
	"'john@doe.com', 'John', 'Doe'",
	"'steve@apple.com', 'Steve', 'Jobs'",
	"'barack@obama.com', 'Barack', 'Obama'",
	"'hillary@clinton.com', 'Hillary', 'Clinton'"
	);

foreach($users as $k => $v) {
	try {
	    $sql = $db->prepare($user_insert . "(" . $v . ", '" . hasher('pwd') . "')" );
	    $sql->execute();
	} catch (Exception $e) {
	    echo "ERROR: User " . $v . " not created!";
	}
}
?>