var read = $.post('php/sendgrid.php', { 'read': true });

read.done
(
	function (data)
	{
		alert(data);
	}
);