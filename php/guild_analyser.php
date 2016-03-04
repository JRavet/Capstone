<?php include 'analyser_header.php'; ?>
<html>
	<title> Guild Analyser </title>
	<body>
	<?php 
	function generate_option($value,$text,$select_name)
	{
		echo "<option value=\"" . $value . "\" ";
		if ($_GET[$select_name] == $value)
		{
			echo "selected=\"true\"";
		}
		echo ">" . $text . "</option>";
	}
	function check_inputs()
	{
		$pattern = '|()[]{}/\\\'\"-+_:;*&^%$#@!?<>,';
		foreach ($_GET as $param)
		{
			if (strpos($pattern,$param) !== false)
			{
				die("Invalid input");
			}
		}
	}
	?>
	<?php
	echo "<form action=\"guild_analyser.php\" method=\"GET\">
	<table>";
	echo "<tr><td>Sort by:</td><td><select name=\"sort_by\">";
	generate_option("count(*)","Claim Count","sort_by");
	generate_option("guild.guild_name","Guild Name","sort_by");
	generate_option("guild.guild_tag","Guild Tag","sort_by");
	echo "</select>
		<tr><td>Match ID: </td><td><input type=\"text\" name=\"match_id\" value=\"" . $_GET["match_id"] . "\"/></td></tr> 
		<tr><td>Week number: </td><td><input type=\"number\" min=\"0\" max=\"52\" name=\"week_num\" value=\"" . $_GET["week_num"] . "\"/></td></tr>
		<tr><td>Owner server: </td><td><input type=\"text\" name=\"obj_owner\" value=\"" . $_GET["obj_owner"] . "\"/></td></tr>
		<tr><td>Owner color: </td><td><input type=\"text\" name=\"owner_color\" value=\"" . $_GET["owner_color"] . "\"/></td></tr>
		<tr><td>Last seized: </td><td><input type=\"datetime\" name=\"last_flipped_begin\" value=\"" . $_GET["last_flipped_begin"] . "\"/></td>
			<td>-</td><td><input type=\"datetime\" name=\"last_flipped_end\" value=\"" . $_GET["last_flipped_end"] . "\"/></td></tr>
		<tr><td>Claimed at: </td><td><input type=\"datetime\" name=\"claimed_at_begin\" value=\"" . $_GET["claimed_at_begin"] . "\"/></td><td>-</td>
			<td><input type=\"datetime\" name=\"claimed_at_end\" value=\"" . $_GET["claimed_at_end"] . "\"/></td></tr>
		<tr><td>In-game clock time: </td><td><input type=\"number\" min=\"1\" max=\"15\" name=\"tick_timer_begin\" value=\"" . $_GET["tick_timer_begin"] . "\"/></td>
			<td>-</td><td><input type=\"number\"min=\"1\" max=\"15\" name=\"tick_timer_end\" value=\"" . $_GET["tick_timer_end"] . "\"/></td></tr>
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
	<td><form action=\"guild_analyser.php\">
		<input type=\"submit\" value=\"Reset fields\"/>
	</form></td>
	</tr>
	</table>";
	?>
	<br/>
	<?php
		$guildClaimQuery = "SELECT count(*) as \"Number of Claims\", guild_name as \"Guild Name\", guild_tag as \"Guild Tag\"
		FROM activity_data
		INNER JOIN server_info ON activity_data.owner_server = server_info.srv_id
		INNER JOIN objective ON activity_data.obj_id=objective.obj_id
		INNER JOIN guild ON guild.guild_id = activity_data.guild_id
		INNER JOIN (SELECT match_id, week_num FROM match_details GROUP BY match_id) as match_details on match_details.match_id=activity_data.match_id
		WHERE guild_name!=\"\" "; //automatically eliminate any activity-data without a guild claim
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
		check_inputs();
		if ($_GET["match_id"] != "")
		{
			$guildClaimQuery .= "and activity_data.match_id = \"" . $_GET["match_id"] . "\" ";
		}
		if ($_GET["week_num"] != "")
		{
			$guildClaimQuery .= "and week_num = \"" . $_GET["week_num"] . "\" ";
		}
		if ($_GET["obj_owner"] != "")
		{
			$guildClaimQuery .= "and server_info.name LIKE \"%" . $_GET["obj_owner"] . "%\" ";
		}
		if ($_GET["owner_color"] != "")
		{
			$guildClaimQuery .= "and owner_color = \"" . $_GET["owner_color"] . "\" ";
		}
		if ($_GET["last_flipped_begin"] != "")
		{
			$guildClaimQuery .= "and last_flipped >= \"" . $_GET["last_flipped_begin"] . "\" ";
		}
		if ($_GET["last_flipped_end"] != "")
		{
			$guildClaimQuery .= "and last_flipped <= \"" . $_GET["last_flipped_end"] . "\" ";
		}
		if ($_GET["claimed_at_begin"] != "")
		{
			$guildClaimQuery .= "and claimed_at >= \"" . $_GET["claimed_at_begin"] . "\" ";
		}
		if ($_GET["claimed_at_end"] != "")
		{
			$guildClaimQuery .= "and claimed_at <= \"" . $_GET["claimed_at_end"] . "\" ";
		}
		if ($_GET["tick_timer_begin"] != "")
		{
			$guildClaimQuery .= "and tick_timer >= \"" . $_GET["tick_timer_begin"] . "\" ";
		}
		if ($_GET["tick_timer_end"] != "")
		{
			$guildClaimQuery .= "and tick_timer <= \"" . $_GET["tick_timer_end"] . "\" ";
		}
		if ($_GET["obj_name"] != "")
		{
			$guildClaimQuery .= "and objective.name LIKE \"%" . $_GET["obj_name"] . "%\" ";
		}
		if ($_GET["obj_type"] != "")
		{
			$guildClaimQuery .= "and objective.type = \"" . $_GET["obj_type"] . "\" ";
		}
		if ($_GET["map_type"] != "")
		{
			$guildClaimQuery .= "and objective.map_type LIKE \"%" . $_GET["map_type"] . "%\" ";
		}
		if ($_GET["guild_name"] != "")
		{
			$guildClaimQuery .= "and guild_name LIKE \"%" . $_GET["guild_name"] . "%\" ";
		}
		if ($_GET["guild_tag"] != "")
		{
			$guildClaimQuery .= "and guild_tag = \"" . $_GET["guild_tag"] . "\" ";
		}
		$guildClaimQuery .= "GROUP BY activity_data.guild_id ";
		$guildClaimQuery .= "ORDER BY " . $_GET["sort_by"] . " DESC;";
		//
		//
		//
		echo "<table border=\"1\">";
		echo "<th>Row #</th><th>Number of Claims</th><th>Guild Name</th><th>Guild Tag</th>";
		$i = 0;
		$resultSet = $conn->query($guildClaimQuery);
		foreach ($resultSet as $row)
		{
			$i++;
			echo "<tr>";
			echo "<td>" . $i . "</td>";
			echo "<td>" . $row["Number of Claims"] . "</td>";
			echo "<td>" . $row["Guild Name"] . "</td>";
			echo "<td>" . $row["Guild Tag"] . "</td>";
			echo "</tr>";
		}
		echo $i . ' results<p>';
		echo "</table>";
	?>
	</body>
</html>
