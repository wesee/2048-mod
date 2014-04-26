var moves;
var moves_valid = ['Up', 'Right', 'Down', 'Left'];
var moving = false;
var rate_move = 500;
var rate_refresh = 1000;

function clear()
{
	moves = [];
	$.post('php/sendgrid.php', { 'clear': true });
	$('#log').html('Move log:<br /><br />Game started.<br />');
	$('#log').css('overflow-y', 'hidden');
}

function move_feed(feed)
{
	var move;

	if (feed.length < 1)
	{
		moving = false;
		return;
	}

	move = feed.shift();
	moves.push(move);
	gm.move(move.move);
	$('#log').append(
		move.from + ' sent move ' + moves_valid[move.move] + '.<br />'
	);

	if (moves.length >= 24)
	{
		alert();
		$('#log').css('overflow-y', 'scroll');
	}

	setTimeout(
		function ()
		{
			move_feed(feed);
		},
		rate_move
	);
}

function read()
{
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
			var feed = jQuery.parseJSON(data).slice(moves.length);
			moving = true;
			move_feed(feed);
		}
	);
}

clear();
read();
setInterval(read, rate_refresh);