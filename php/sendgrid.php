<?php
include 'config.inc.php';

$MYSQLI = new mysqli(
	$CONFIG['host'], $CONFIG['username'], $CONFIG['password'], $CONFIG['db']
);
$GRID = array();
$SCORE = 0;

for ($x = 0; $x < 4; $x++)
{
	$column = array();

	for ($y = 0; $y < 4; $y++)
	{
		$column[] = -1;
	}

	$GRID[] = $column;
}

function best_score()
{
	global $MYSQLI;
	$query = 'SELECT `value` FROM `settings` WHERE `key` = \'best_score\';';
	$result = $MYSQLI->query($query);
	$best_score = $result->fetch_assoc();
	$result->close();
	return intval($best_score['value']);
}

function best_score_update()
{
	global $MYSQLI, $SCORE;

	if ($SCORE <= best_score())
	{
		return;
	}

	$query = 'UPDATE `settings` SET `value` = ? WHERE `key` = \'best_score\';';
	$stmt = $MYSQLI->prepare($query);
	$stmt->bind_param('i', $SCORE);
	$stmt->execute() or die('MySQL Error: ' . $MYSQLI->error.__LINE__);
	$stmt->close();
}

function clear()
{
	global $MYSQLI;
	$query = 'TRUNCATE TABLE `moves`;';
	$MYSQLI->query($query);
	return 'Success';
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

function game_over()
{
	for ($move = 0; $move < 4; $move++)
	{
		if (move_grid($move))
		{
			return;
		}
	}

	clear();
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

	if (!move_grid($move))
	{
		return 'Failure';
	}

	$random = tile_random();
	$tile = $random['tile'];
	$x = $random['x'];
	$y = $random['y'];
	// Insert move into database.
	$query = 'INSERT INTO `moves` (`from`, `move`, `tile`, `x`, `y`, `time`)
		VALUES (?, ?, ?, ?, ?, ?);';
	$stmt = $MYSQLI->prepare($query);
	$stmt->bind_param('siiiii', $from, $move, $tile, $x, $y, mktime());
	$stmt->execute() or die(
		'MySQL Error: ' . $MYSQLI->error.__LINE__
	);
	$stmt->close();
	$GRID[$x][$y] = $tile;

	return 'Success';
}

function move_grid($move)
{
	global $GRID, $SCORE;
	$merged = array();
	$moved = false;

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
					if ($GRID[$x][$y] == -1)
					{
						continue;
					}

					$y2 = $y;

					while ($y2 >= 0 && $GRID[$x][$y2 - 1] == -1)
					{
						$tile = $GRID[$x][$y2];
						$GRID[$x][$y2] = -1;
						$y2--;
						$GRID[$x][$y2] = $tile;
						$moved = true;
					}

					if (
						$y2 >= 0 && $GRID[$x][$y2] == $GRID[$x][$y2 - 1] &&
						!$merged[$x][$y2 - 1]
					)
					{
						$tile = $GRID[$x][$y2];
						$GRID[$x][$y2] = -1;
						$y2--;
						$GRID[$x][$y2] = $tile * 2;
						$SCORE += $GRID[$x][$y2];
						$merged[$x][$y2] = true;
						$moved = true;
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
					if ($GRID[$x][$y] == -1)
					{
						continue;
					}

					$x2 = $x;

					while ($x2 < 4 && $GRID[$x2 + 1][$y] == -1)
					{
						$tile = $GRID[$x2][$y];
						$GRID[$x2][$y] = -1;
						$x2++;
						$GRID[$x2][$y] = $tile;
						$moved = true;
					}

					if (
						$x2 < 4 && $GRID[$x2][$y] == $GRID[$x2 + 1][$y] &&
						!$merged[$x2 + 1][$y]
					)
					{
						$tile = $GRID[$x2][$y];
						$GRID[$x2][$y] = -1;
						$x2++;
						$GRID[$x2][$y] = $tile * 2;
						$SCORE += $GRID[$x2][$y];
						$merged[$x2][$y] = true;
						$moved = true;
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
					if ($GRID[$x][$y] == -1)
					{
						continue;
					}

					$y2 = $y;

					while ($y2 < 4 && $GRID[$x][$y2 + 1] == -1)
					{
						$tile = $GRID[$x][$y2];
						$GRID[$x][$y2] = -1;
						$y2++;
						$GRID[$x][$y2] = $tile;
						$moved = true;
					}

					if (
						$y2 < 4 && $GRID[$x][$y2] == $GRID[$x][$y2 + 1] &&
						!$merged[$x][$y2 + 1]
					)
					{
						$tile = $GRID[$x][$y2];
						$GRID[$x][$y2] = -1;
						$y2++;
						$GRID[$x][$y2] = $tile * 2;
						$SCORE += $GRID[$x][$y2];
						$merged[$x][$y2] = true;
						$moved = true;
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
					if ($GRID[$x][$y] == -1)
					{
						continue;
					}

					$x2 = $x;

					while ($x2 >= 0 && $GRID[$x2 - 1][$y] == -1)
					{
						$tile = $GRID[$x2][$y];
						$GRID[$x2][$y] = -1;
						$x2--;
						$GRID[$x2][$y] = $tile;
						$moved = true;
					}

					if (
						$x2 >= 0 && $GRID[$x2][$y] == $GRID[$x2 - 1][$y] &&
						!$merged[$x2 - 1][$y]
					)
					{
						$tile = $GRID[$x2][$y];
						$GRID[$x2][$y] = -1;
						$x2--;
						$GRID[$x2][$y] = $tile * 2;
						$SCORE += $GRID[$x2][$y];
						$merged[$x2][$y] = true;
						$moved = true;
					}
				}
			}
		break;

		default:
			return true;
	}

	return $moved;
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
	$query = 'SELECT `from`, `move`, `tile`, `x`, `y`, `time`
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
	read();
	$move = move_parse($_POST['text']);

	if ($move !== false)
	{
		echo move($_POST['from'], $move);
	}

	best_score_update();
	game_over();
}

// Clear Moves.
elseif ($_POST['clear'])
{
	echo clear();
}

// Read Moves.
elseif ($_POST['read'])
{
	$moves = read();

	if (empty($moves))
	{
		initialize();
		$moves = read();
	}

	best_score_update();
	game_over();
	echo json_encode(
		array(
			'best_score' => best_score(), 'grid' => $GRID, 'moves' => $moves
		)
	);
	exit;
}
?>

<form action="#" method="post">
<p>
	<input type="hidden" name="from" value="Brandon Evans" />
	<input type="submit" name="text" value="Up" />
	<input type="submit" name="text" value="Right" />
	<input type="submit" name="text" value="Down" />
	<input type="submit" name="text" value="Left" />
</p>

<p>
	<input type="submit" name="read" value="Read" />
	<input type="submit" name="clear" value="Clear" />
</p>
</form>