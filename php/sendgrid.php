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

	foreach ($moves_valid as $move => $str)
	{
		$pos = strpos($text, $str);

		if ($pos !== false)
		{
			// Insert move into database.
			$query = 'INSERT INTO `moves` (`move`) VALUES (?);';
			$stmt = $MYSQLI->prepare($query);
			$stmt->bind_param('i', $move);
			$stmt->execute() or die(
				'MySQL Error: ' . $MYSQLI->error.__LINE__
			);
			$stmt->close();
		}
	}
}

// Clear Moves.
if ($_POST['clear'])
{
	$query = 'TRUNCATE TABLE `moves`;';
	$stmt = $MYSQLI->query($query);
	$stmt->close();
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
	$result->close();

	echo json_encode($moves);
}
?>