<?php
include 'config.inc.php';

$MYSQLI = new mysqli(
	$CONFIG['host'], $CONFIG['username'], $CONFIG['password'], $CONFIG['db']
);

if ($_POST['read'])
{
	$moves = array();
	$query = 'SELECT `move` FROM `moves`;';
	$result = $MYSQLI->query($query);

	while ($move = $result->fetch_assoc())
	{
		$moves[] = $move['move'];
	}

	echo json_encode($moves);
}
?>