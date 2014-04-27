var mobile = (
	/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(
		navigator.userAgent.toLowerCase()
	)
);
var moves = [];
var moves_valid = ['Up', 'Right', 'Down', 'Left'];
var moving = false;
var rate_refresh = 1000;

String.prototype.repeat = function( num )
{
    return new Array( num + 1 ).join( this );
}

function grid_text(grid)
{
	text = '';

	for (var y = 0; y < 4; y++)
	{
		text += '|';

		for (var x = 0; x < 4; x++)
		{
			if (grid[x][y] >= 2)
			{
				tile = grid[x][y] + '';
				text += tile + ' '.repeat(4 - tile.length);
			}
			else
			{
				text += '    ';
			}

			text += '|';
		}

		text += '\n';
	}

	return text;
}

function log_reset()
{
	$('#log').html('Game started.<br />');
}

function move_feed(feed)
{
	var move;
	var tile;

	if (feed.length < 1)
	{
		moving = false;
		return;
	}

	move = feed.shift();
	moves.push(move);

	if (move.move >= 0 && move.move <= 3)
	{
		gm.move(move.move);
		$('#log').append(
			document.createTextNode(
				move.from + ' sent move ' + moves_valid[move.move] + ' at ' +
					timestamp_text(move.time)
			)
		);
		$('#log').append('<br />');
	}

	tile = new Tile(
		{'x': parseInt(move.x), 'y': parseInt(move.y)}, parseInt(move.tile)
	);
	gm.grid.insertTile(tile);
	gm.actuate();
	$('#log').append(
		move.tile + ' tile generated at (' + move.x + ', ' + move.y +
			').<br />'
	);
	$('#log').scrollTop($('#log')[0].scrollHeight);

	move_feed(feed);
}

function read()
{
	var moves_feed;

	if (moving)
	{
		return;
	}

	var result = $.post('php/sendgrid.php', { 'read': true });

	result.done
	(
		function (data)
		{
			// Feed the new moves.
			var feed = jQuery.parseJSON(data);

			if (
				moves.length > 0 &&
				(feed.moves.length <= 0 || moves[0].time != feed.moves[0].time)
			)
			{
				moves = [];
				gm.restart();
				log_reset();
			}

			moves_feed = feed.moves.slice(moves.length);
			moving = true;
			move_feed(moves_feed);
			gm.storageManager.setBestScore(feed.best_score);
			$('#grid').text(grid_text(feed.grid));
		}
	);
}

function timestamp_text(timestamp)
{
	var date = new Date(timestamp * 1000);
	return date.getHours() + ':' + date.getMinutes() + ':' +
		date.getSeconds() + '.' + date.getMilliseconds();
}

$(document).ready
(
    function ()
    {
		if (mobile)
		{
			$('#log').css('height', '295px');
		}

		log_reset();
		read();
		setInterval(read, rate_refresh);
	}
);