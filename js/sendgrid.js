var moves = []
var read = $.post('php/sendgrid.php', { 'read': true });

function move_feed(feed)
{
	var move;

	if (feed.length < 1)
	{
		return;
	}

	move = feed.shift();
	gm.move(move);

	setTimeout(
		function ()
		{
			move_feed(feed);
		},
		500
	);
}

read.done
(
	function (data)
	{
		var feed = jQuery.parseJSON(data);
		move_feed(feed);
	}
);