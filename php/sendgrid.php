<?php
include 'config.inc.php';

$MYSQLI = new mysqli(
	$CONFIG['host'], $CONFIG['username'], $CONFIG['password'], $CONFIG['db']
);

if ($_POST['read'])
{
	$moves = array(0, 1, 2, 3, 0);
	echo json_encode($moves);
}
?>