<?php
include 'config.inc.php';

$MYSQLI = new mysqli(
	$CONFIG['host'], $CONFIG['username'], $CONFIG['password'], $CONFIG['db']
);
$GRID = array();

for ($x = 0; $x < 4; $x++)
{
	$column = array();

	for ($y = 0; $y < 4; $y++)
	{
		$column[] = -1;
	}

	$GRID[] = $column;
}

function clear()
{
	global $MYSQLI;
	$query = 'TRUNCATE TABLE `moves`;';
	$stmt = $MYSQLI->query($query);
	$stmt->close();
}

function clear_grid()
{
	global $GRID;

	for ($x = 0; $x < 4; $x++)
	{
		for ($y = 0; $y < 4; $y++)
		{
			$GRID[$x][$y] = -1;
		}
	}
}

function initialize()
{
	global $GRID;

	clear_grid();

	for ($tile = 0; $tile < 2; $tile++)
	{
		move('', -1);
	}
}

function move($from, $move)
{
	global $GRID, $MYSQLI;
	$random = tile_random();
	$tile = $random['tile'];
	$x = $random['x'];
	$y = $random['y'];
	// Insert move into database.
	$query = 'INSERT INTO `moves` (`from`, `move`, `tile`, `x`, `y`)
		VALUES (?, ?, ?, ?, ?);';
	$stmt = $MYSQLI->prepare($query);
	$stmt->bind_param('siiii', $from, $move, $tile, $x, $y);
	$stmt->execute() or die(
		'MySQL Error: ' . $MYSQLI->error.__LINE__
	);
	$stmt->close();
	$GRID[$x][$y] = $tile;
}

function move_parse($text)
{
	$moves_valid = array('up', 'right', 'down', 'left');
	$text = trim(strtolower($text));

	foreach ($moves_valid as $move => $str)
	{
		if (strpos($text, $str) !== false)
		{
			return $move;
		}
	}

	return false;
}

function read()
{
	global $GRID, $MYSQLI;
	clear_grid();
	$moves = array();
	$query = 'SELECT `from`, `move`, `tile`, `x`, `y`
		FROM `moves` ORDER BY `id`;';
	$result = $MYSQLI->query($query);

	while ($move = $result->fetch_assoc())
	{
		$moves[] = $move;

		$GRID[$move['x']][$move['y']] = $move['tile'];
	}
	$result->close();

	return $moves;
}

function tile_random()
{
	global $GRID;
	// Make the tile 2, or a 4 10% of the time.
	$tile = (rand(0, 9) < 9) ? 2 : 4;

	// Select a random available cell.
	do
	{
		$x = rand(0, 3);
		$y = rand(0, 3);
	}
	while ($GRID[$x][$y] != -1);

	return array('tile' => $tile, 'x' => $x, 'y' => $y);
}

// Handle E-Mail.
if ($_POST['text'])
{
	$move = move_parse($_POST['text']);

	if ($move !== false)
	{
		move($_POST['from'], $move);
	}
}

// Clear Moves.
if ($_POST['clear'])
{
	clear();
}

// Read Moves.
if ($_POST['read'])
{
	$moves = read();

	if (empty($moves))
	{
		initialize();
		$moves = read();
	}

	echo json_encode(array('grid' => $GRID, 'moves' => $moves));
}
?>