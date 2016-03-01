<?php
	session_start();
	try
	{
		$conn = new PDO("mysql:host=localhost;dbname=Gw2Analyser", $_SESSION["username"], $_SESSION["password"]);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch(PDOException $e)
	{
		header("Location: logout.php?error=inv_cred"); //manually add a GET variable to tell the index-login page there was an error
	}
?>
<html>
	<title> Activity Analyser </title>
	<body>
	<?php
	echo "<form action=\"logout.php\" method=\"GET\">
		<input type=\"submit\" value=\"Log out\"/>
	</form>
	<form action=\"activity_analyser.php\" method=\"GET\">
	<table>";
	echo "<tr><td>Match ID: </td><td><input type=\"text\" name=\"match_id\" value=\"" . $_GET["match_id"] . "\"/></td></tr> 
		<tr><td>Week number: </td><td><input type=\"text\" name=\"week_num\" value=\"" . $_GET["week_num"] . "\"/></td></tr>
		<tr><td>Objective owner: </td><td><input type=\"text\" name=\"obj_owner\" value=\"" . $_GET["obj_owner"] . "\"/></td></tr>
		<tr><td>Owner color: </td><td><input type=\"text\" name=\"owner_color\" value=\"" . $_GET["owner_color"] . "\"/></td></tr>
		<tr><td>Last seized: </td><td><input type=\"text\" name=\"last_flipped_begin\" value=\"" . $_GET["last_flipped_begin"] . "\"/></td>
			<td>-</td><td><input type=\"text\" name=\"last_flipped_end\" value=\"" . $_GET["last_flipped_end"] . "\"/></td></tr>
		<tr><td>Claimed at: </td><td><input type=\"text\" name=\"claimed_at_begin\" value=\"" . $_GET["claimed_at_begin"] . "\"/></td><td>-</td>
			<td><input type=\"text\" name=\"claimed_at_end\" value=\"" . $_GET["claimed_at_end"] . "\"/></td></tr>
		<tr><td>In-game clock time: </td><td><input type=\"text\" name=\"tick_timer_begin\" value=\"" . $_GET["tick_timer_begin"] . "\"/></td>
			<td>-</td><td><input type=\"text\" name=\"tick_timer_end\" value=\"" . $_GET["tick_timer_end"] . "\"/></td></tr>
		<tr><td>Objective name: </td><td><input type=\"text\" name=\"obj_name\" value=\"" . $_GET["obj_name"] . "\"/></td></tr>
		<tr><td>Objective type: </td><td><input type=\"text\" name=\"obj_type\" value=\"" . $_GET["obj_type"] . "\"/></td></tr>
		<tr><td>Map type: </td><td><input type=\"text\" name=\"map_type\" value=\"" . $_GET["map_type"] . "\"/></td></tr>
		<tr><td>Guild name: </td><td><input type=\"text\" name=\"guild_name\" value=\"" . $_GET["guild_name"] . "\"/></td></tr>
		<tr><td>Guild tag: </td><td><input type=\"text\" name=\"guild_tag\" value=\"" . $_GET["guild_tag"] . "\"/></td></tr>";
	echo "</table>
	<table>
	<tr>
	<td><input type=\"submit\" value=\"Submit Query\"/></td><td style=\"width:175px\"></td>
	</form></td>
	<td><form action=\"activity_analyser.php\">
		<input type=\"submit\" value=\"Reset fields\"/>
	</form></td>
	</tr>
	</table>";
	?>
	<br/>
	<?php
		$activityQuery = "SELECT activity_data.match_id as \"Match ID\", week_num as \"Week Number\",
		server_info.name as \"Server\", owner_color as \"Color\", last_flipped as \"Last Seized At\",
		claimed_at as \"Claimed At\", tick_timer as \"Tick Timer\", objective.name as \"Objective Name\",
		type as \"Objective Type\", compass_direction as \"Cardinal Direction\", map_type as \"Map\",
		guild_name as \"Guild Name\", guild_tag as \"Guild Tag\"
		FROM activity_data
		INNER JOIN guild ON activity_data.guild_id=guild.guild_id
		INNER JOIN objective ON activity_data.obj_id=objective.obj_id
		INNER JOIN server_info on activity_data.owner_server=server_info.srv_id
		INNER JOIN (SELECT match_id, week_num FROM match_details GROUP BY match_id) as match_details on match_details.match_id=activity_data.match_id
		WHERE activity_data.match_id = activity_data.match_id "; //spoof WHERE clause
		
		$activityQuery .= "and activity_data.match_id = \"1-4\" and guild_tag = \"Os\"
		ORDER BY last_flipped;";
		?>
		<?php
			echo "<table border=\"1\">";
			echo "<th>Match ID</th><th>Week Number</th><th>Server</th><th>Color</th>
			<th>Last Seized At</th><th>Claimed At</th><th>Tick Timer</th><th>Objective Name</th>
			<th>Objective Type</th><th>Cardinal Direction</th><th>Map</th><th>Guild Name</th><th>Guild Tag</th>";
			foreach ($conn->query($activityQuery) as $row)
			{
				echo "<tr>";
				echo "<td>" . $row["Match ID"] . "</td>";
				echo "<td>" . $row["Week Number"] . "</td>";
				echo "<td>" . $row["Server"] . "</td>";
				echo "<td>" . $row["Color"] . "</td>";
				echo "<td>" . $row["Last Seized At"] . "</td>";
				echo "<td>" . $row["Claimed At"] . "</td>";
				echo "<td>" . $row["Tick Timer"] . "</td>";
				echo "<td>" . $row["Objective Name"] . "</td>";
				echo "<td>" . $row["Objective Type"] . "</td>";
				echo "<td>" . $row["Cardinal Direction"] . "</td>";
				echo "<td>" . $row["Map"] . "</td>";
				echo "<td>" . $row["Guild Name"] . "</td>";
				echo "<td>" . $row["Guild Tag"] . "</td>";
				echo "</tr>";
			}
			echo "</table>";
		?>
	</body>
</html>
