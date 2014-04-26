<?php
include 'config.inc.php';

$MYSQLI = new mysqli(
	$CONFIG['host'], $CONFIG['username'], $CONFIG['password'], $CONFIG['db']
);

// Handle E-Mail.
if ($_POST['text'])
{
	$moves_valid = array('up', 'right', 'down', 'left');
	$text = trim(strtolower($_POST['text']));
	$move = array_search($text, $moves_valid);

	if ($move !== false)
	{
		// Insert move into database.
		$query = 'INSERT INTO `moves` (`move`) VALUES (?);';
		$result = $MYSQLI->prepare($query);
		$result->bind_param('i', $move);
		$result->execute() or die('MySQL Error: ' . $MYSQLI->error.__LINE__);
		$result->close();
	}
}

// Read Moves.
if ($_POST['read'])
{
	$moves = array();
	$query = 'SELECT `move` FROM `moves` ORDER BY `id`;';
	$result = $MYSQLI->query($query);

	while ($move = $result->fetch_assoc())
	{
		$moves[] = $move['move'];
	}

	echo json_encode($moves);
}
?>