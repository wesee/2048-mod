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
	move_grid($move);
	$GRID[$x][$y] = $tile;
}

function move_grid($move)
{
	global $GRID;
	$merged = array();

	for ($x = 0; $x < 4; $x++)
	{
		for ($y = 0; $y < 4; $y++)
		{
			$merged[$x][$y] = false;
		}
	}

	switch ($move)
	{
		// Up
		case 0:
			for ($y = 1; $y < 4; $y++)
			{
				for ($x = 0; $x < 4; $x++)
				{
					$y2 = $y;

					while ($y2 >= 0 && $GRID[$x][$y2 - 1] == -1)
					{
						$tile = $GRID[$x][$y2];
						$GRID[$x][$y2] = -1;
						$y2--;
						$GRID[$x][$y2] = $tile;
					}

					if (
						$y2 >= 0 && $GRID[$x][$y2] == $GRID[$x][$y2 - 1] &&
						!$merged[$x][$y2 - 1]
					)
					{
						$GRID[$x][$y2] = -1;
						$y2--;
						$GRID[$x][$y2] = $tile * 2;
						$merged[$x][$y2] = true;
					}
				}
			}
		break;

		// Right
		case 1:
			for ($x = 2; $x >= 0; $x--)
			{
				for ($y = 0; $y < 4; $y++)
				{
					$x2 = $x;

					while ($x2 < 4 && $GRID[$x2 + 1][$y] == -1)
					{
						$tile = $GRID[$x2][$y];
						$GRID[$x2][$y] = -1;
						$x2++;
						$GRID[$x2][$y] = $tile;
					}

					if (
						$x2 < 4 && $GRID[$x2][$y] == $GRID[$x2 + 1][$y] &&
						!$merged[$x2 + 1][$y]
					)
					{
						$GRID[$x2][$y] = -1;
						$x2++;
						$GRID[$x2][$y] = $tile * 2;
						$merged[$x2][$y] = true;
					}
				}
			}
		break;

		// Down
		case 2:
			for ($y = 2; $y >= 0; $y--)
			{
				for ($x = 0; $x < 4; $x++)
				{
					$y2 = $y;

					while ($y2 < 4 && $GRID[$x][$y2 + 1] == -1)
					{
						$tile = $GRID[$x][$y2];
						$GRID[$x][$y2] = -1;
						$y2++;
						$GRID[$x][$y2] = $tile;
					}

					if (
						$y2 < 4 && $GRID[$x][$y2] == $GRID[$x][$y2 + 1] &&
						!$merged[$x][$y2 + 1]
					)
					{
						$GRID[$x][$y2] = -1;
						$y2++;
						$GRID[$x][$y2] = $tile * 2;
						$merged[$x][$y2] = true;
					}
				}
			}
		break;

		// Left
		case 3:
			for ($x = 1; $x < 4; $x++)
			{
				for ($y = 0; $y < 4; $y++)
				{
					$x2 = $x;

					while ($x2 >= 0 && $GRID[$x2 - 1][$y] == -1)
					{
						$tile = $GRID[$x2][$y];
						$GRID[$x2][$y] = -1;
						$x2--;
						$GRID[$x2][$y] = $tile;
					}

					if (
						$x2 >= 0 && $GRID[$x2][$y] == $GRID[$x2 - 1][$y] &&
						!$merged[$x2 - 1][$y]
					)
					{
						$GRID[$x2][$y] = -1;
						$x2--;
						$GRID[$x2][$y] = $tile * 2;
						$merged[$x2][$y] = true;
					}
				}
			}
		break;
	}
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
		move_grid($move['move']);
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