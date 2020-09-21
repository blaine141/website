<?php
function console($text)
	{
		$text = str_replace(array("\r","\n"),"",$text);
		echo "<script>
				console.log(" . '"' . $text . '"' . ")
			  </script>";
	}
	function query($queryString)
	{
		console($queryString);
		$return = mysql_query($queryString);
		if ($return == false)
			console(mysql_error());
		return $return;
	}
	$hostname = "blaine.dyndns.biz";
	$username = "blaine141";
	$password = "blaine141";
	mysql_connect($hostname, $username, $password) OR DIE ("Unable to 
	connect to database! Please try again later.");
	$ip = $_SERVER['REMOTE_ADDR'];
	$details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
	$location = $details->city.', '.$details->region;
	$today = getdate();
	$time = $today['mon'].'/'.$today['mday'].'/'.$today['year'];
	if (!empty($_POST))
		query("INSERT INTO website.lamp (ip,location,time) VALUES ('$ip','$location','$time')");
	if (isset($_POST['on']))
	{
		exec(escapeshellcmd('LampOn.exe'));
	}
	if (isset($_POST['off']))
	{
	
		exec(escapeshellcmd('LampOff.exe'));
	}
	echo "<head>
  <title>Lamp Controller</title>
  <meta content='application/xhtml; charset=UTF-8'
 http-equiv='content-type' />
  <link media='screen, tv, projection' href='../css/style.css'
 type='text/css' rel='stylesheet' />
</head>
<body>
<div id='container'>
<div id='logo'>
<h1><span class='green'>Blaine's</span> Website</h1>
</div>
<div class='br'></div>
<div id='navlist'>
<ul>
  <li><a href='../index.html'>Home</a></li>
  <li><a href='../downloads'>Downloads</a></li>
  <li><a href='../projects' class='active'>Projects</a></li>
</ul>
</div>
<div id='content'>
<h3>› Lamp Controller</h3>
<p> Using the X10 Firecracker and MVC, a voice recognition program, I have given my computer the capabilities of turning on and off my lamp. Using the buttons below, you can do the same by pushing the buttons below.</p>
<p align='center'>";
	
	$result = mysql_query("SELECT ip FROM website.lamp WHERE id=1");
	$row = mysql_fetch_array($result);
	$status = $row['ip'];
	if ($status == 0)
		echo "The lamp is off";
	else
		echo "The lamp is on";
	echo "</p>
		  <form action='#' method='POST' align='center'>
			<input type='submit' name='on' value='On' style='width:100px'>&nbsp;&nbsp;&nbsp;
			<input type='submit' name='off' value='Off' style='width:100px'>
		  </form><br>
		  <p align=center> Last changed by ";
	$result = mysql_query("SELECT * FROM website.lamp");
	while($row = mysql_fetch_array($result))
	{
		$ip = $row['ip'];
		$time = $row['time'];
		$location = $row['location'];
	}
	echo "$ip from $location on $time";
?>