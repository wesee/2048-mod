var moves = []
var moving = false;

function move_feed(feed)
{
	var move;

	if (feed.length < 1)
	{
		moving = false;
		return;
	}

	move = feed.shift();
	gm.move(move);
	moves.push(move);

	setTimeout(
		function ()
		{
			move_feed(feed);
		},
		500
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

read();
setInterval(read, 500);