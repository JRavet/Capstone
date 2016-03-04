<?php include 'analyser_header.php'; ?>
<html>
	<title> Activity Analyser </title>
	<body>
	<?php
	echo "<form action=\"activity_analyser.php\" method=\"GET\">
	<table>";
	echo "<tr><td>Sort by:</td><td><select name=\"sort_by\">
			<option value=\"last_flipped\">Last Seized At</option>
			<option value=\"activity_data.match_id\">Match ID</option>
			<option value=\"week_num\">Week Number</option>
			<option value=\"server_info.name\">Objective Owner</option>
			<option value=\"owner_color\">Owner Color</option>
			<option value=\"claimed_at\">Claimed At</option>
			<option value=\"tick_timer\">In-game clock time</option>
			<option value=\"objective.name\">Objective Name</option>
			<option value=\"objective.type\">Objective Type</option>
			<option value=\"objective.map_type\">Map</option>
			<option value=\"guild.guild_name\">Guild Name</option>
			<option value=\"guild.guild_tag\">Guild Tag</option>
		</select>
		<tr><td>Match ID: </td><td><input type=\"text\" name=\"match_id\" value=\"" . $_GET["match_id"] . "\"/></td></tr> 
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
		claimed_at as \"Claimed At\", tick_timer as \"Ingame Clock\", objective.name as \"Objective Name\",
		type as \"Objective Type\", compass_direction as \"Cardinal Direction\", map_type as \"Map\",
		guild_name as \"Guild Name\", guild_tag as \"Guild Tag\"
		FROM activity_data
		INNER JOIN guild ON activity_data.guild_id=guild.guild_id
		INNER JOIN objective ON activity_data.obj_id=objective.obj_id
		INNER JOIN server_info on activity_data.owner_server=server_info.srv_id
		INNER JOIN (SELECT match_id, week_num FROM match_details GROUP BY match_id) as match_details on match_details.match_id=activity_data.match_id
		WHERE activity_data.match_id = activity_data.match_id "; //spoof WHERE clause to allow additional clauses prefixed with AND
		if (
			$_GET["match_id"] == "" and $_GET["week_num"] == "" and $_GET["obj_owner"] == ""
			and $_GET["owner_color"] == "" and $_GET["last_flipped_begin"] == "" and $_GET["last_flipped_end"] == ""
			and $_GET["claimed_at_begin"] == "" and $_GET["claimed_at_end"] == "" and $_GET["tick_timer_begin"] == ""
			and $_GET["tick_timer_end"] == "" and $_GET["obj_name"] == "" and $_GET["obj_type"] == ""
			and $_GET["map_type"] == "" and $_GET["guild_name"] == "" and $_GET["guild_tag"] == ""
		)
		{
			die(""); //if the user did not enter any search criteria, stop early
		}
		if ($_GET["match_id"] != "")
		{
			$activityQuery .= "and activity_data.match_id = \"" . $_GET["match_id"] . "\" ";
		}
		if ($_GET["week_num"] != "")
		{
			$activityQuery .= "and week_num = \"" . $_GET["week_num"] . "\" ";
		}
		if ($_GET["obj_owner"] != "")
		{
			$activityQuery .= "and server_info.name LIKE \"%" . $_GET["obj_owner"] . "%\" ";
		}
		if ($_GET["owner_color"] != "")
		{
			$activityQuery .= "and owner_color = \"" . $_GET["owner_color"] . "\" ";
		}
		if ($_GET["last_flipped_begin"] != "")
		{
			$activityQuery .= "and last_flipped >= \"" . $_GET["last_flipped_begin"] . "\" ";
		}
		if ($_GET["last_flipped_end"] != "")
		{
			$activityQuery .= "and last_flipped <= \"" . $_GET["last_flipped_end"] . "\" ";
		}
		if ($_GET["claimed_at_begin"] != "")
		{
			$activityQuery .= "and claimed_at >= \"" . $_GET["claimed_at_begin"] . "\" ";
		}
		if ($_GET["claimed_at_end"] != "")
		{
			$activityQuery .= "and claimed_at <= \"" . $_GET["claimed_at_end"] . "\" ";
		}
		if ($_GET["tick_timer_begin"] != "")
		{
			$activityQuery .= "and tick_timer >= \"" . $_GET["tick_timer_begin"] . "\" ";
		}
		if ($_GET["tick_timer_end"] != "")
		{
			$activityQuery .= "and tick_timer <= \"" . $_GET["tick_timer_end"] . "\" ";
		}
		if ($_GET["obj_name"] != "")
		{
			$activityQuery .= "and objective.name LIKE \"%" . $_GET["obj_name"] . "%\" ";
		}
		if ($_GET["obj_type"] != "")
		{
			$activityQuery .= "and objective.type = \"" . $_GET["obj_type"] . "\" ";
		}
		if ($_GET["map_type"] != "")
		{
			$activityQuery .= "and objective.map_type LIKE \"%" . $_GET["map_type"] . "%\" ";
		}
		if ($_GET["guild_name"] != "")
		{
			$activityQuery .= "and guild_name LIKE \"%" . $_GET["guild_name"] . "%\" ";
		}
		if ($_GET["guild_tag"] != "")
		{
			$activityQuery .= "and guild_tag = \"" . $_GET["guild_tag"] . "\" ";
		}
		$activityQuery .= "ORDER BY " . $_GET["sort_by"] . " LIMIT 500;";
		//
		//
		//
		echo "<table border=\"1\">";
		echo "<th>Row #</th><th>Match ID</th><th>Week Number</th><th>Server</th><th>Color</th>
		<th>Last Seized At</th><th>Claimed At</th><th>Ingame Clock</th><th>Objective Name</th>
		<th>Objective Type</th><th>Cardinal Direction</th><th>Map</th><th>Guild Name</th><th>Guild Tag</th>";
		$time_start = microtime(true); 
		$i = 0;
		$resultSet = $conn->query($activityQuery);
		foreach ($resultSet as $row)
		{
			$i++;
			echo "<tr>";
			echo "<td>" . $i . "</td>";
			echo "<td>" . $row["Match ID"] . "</td>";
			echo "<td>" . $row["Week Number"] . "</td>";
			echo "<td>" . $row["Server"] . "</td>";
			echo "<td>" . $row["Color"] . "</td>";
			echo "<td>" . $row["Last Seized At"] . "</td>";
			echo "<td>" . $row["Claimed At"] . "</td>";
			echo "<td>" . $row["Ingame Clock"] . "</td>";
			echo "<td>" . $row["Objective Name"] . "</td>";
			echo "<td>" . $row["Objective Type"] . "</td>";
			echo "<td>" . $row["Cardinal Direction"] . "</td>";
			echo "<td>" . $row["Map"] . "</td>";
			echo "<td>" . $row["Guild Name"] . "</td>";
			echo "<td>" . $row["Guild Tag"] . "</td>";
			echo "</tr>";
			if ($i == 500)
			{
				echo "Limited to 500 results.<p>";
			}
		}
		echo $i . ' results in  ' . (microtime(true) - $time_start)*1000 . ' milliseconds';
		echo "</table>";
	?>
	</body>
</html>
