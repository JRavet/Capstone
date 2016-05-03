<?php
include 'analyser_header.php';
include 'bootstrap_styling.php';
?>
<html>
	<title> Activity Analyser </title>
	<style>body{background:#FFF;}</style>
	<body>
	<?php
	echo "<form action=\"activity_analyser.php\" method=\"GET\">
	<div class=\"col-sm-12\">
	<table class=\"table-condensed\">";
	echo "<tr><td>Sort by:</td><td><select name=\"sort_by1\">";
		generate_option("timeStamp","Time Stamp","sort_by1");
		generate_option("activity_data.match_id","Match ID","sort_by1");
		generate_option("last_flipped","Last Seized At","sort_by1");
		generate_option("week_num","Week Number","sort_by1");
		generate_option("server_info.name","Owner Server","sort_by1");
		generate_option("owner_color","Owner Color","sort_by1");
		generate_option("claimed_at","Claimed At","sort_by1");
		generate_option("tick_timer","In-game Clock Time","sort_by1");
		generate_option("objective.name","Objective Name","sort_by1");
		generate_option("objective.type","Objective Type","sort_by1");
		generate_option("objective.map_type","Map","sort_by1");
		generate_option("guild.guild_name","Guild Name","sort_by1");
		generate_option("guild.guild_tag","Guild Tag","sort_by1");
		generate_option("duration_owned","Duration Owned","sort_by1");
		generate_option("duration_claimed","Duration Claimed","sort_by1");
	echo "</select></td><td><td><select name=\"sort_by2\">";
		generate_option("activity_data.match_id","Match ID","sort_by2");
		generate_option("timeStamp","Time Stamp","sort_by2");
		generate_option("last_flipped","Last Seized At","sort_by2");
		generate_option("week_num","Week Number","sort_by2");
		generate_option("server_info.name","Owner Server","sort_by2");
		generate_option("owner_color","Owner Color","sort_by2");
		generate_option("claimed_at","Claimed At","sort_by2");
		generate_option("tick_timer","In-game Clock Time","sort_by2");
		generate_option("objective.name","Objective Name","sort_by2");
		generate_option("objective.type","Objective Type","sort_by2");
		generate_option("objective.map_type","Map","sort_by2");
		generate_option("guild.guild_name","Guild Name","sort_by2");
		generate_option("guild.guild_tag","Guild Tag","sort_by2");
		generate_option("duration_owned","Duration Owned","sort_by2");
		generate_option("duration_claimed","Duration Claimed","sort_by2");
		echo "</select></td><tr><td>Time Stamp: </td><td><input type=\"datetime\" placeholder=\"YYYY-MM-DD hh:mm:ss\" name=\"timeStamp_begin\" value=\"" . $_GET["timeStamp_begin"] . "\"/></td>
			<td>-</td><td><input type=\"datetime\" placeholder=\"YYYY-MM-DD hh:mm:ss\" name=\"timeStamp_end\" value=\"" . $_GET["timeStamp_end"] . "\"/></td></tr>
		<tr><td>Region / Match Number:</td><td> <select name=\"region\">";
		generate_option("","","region");
		generate_option("1","1 (NA)","region");
		generate_option("2","2 (EU)","region");
		echo "</select><input type=\"number\" min=\"1\" max=\"9\" name=\"match_num\" value=\"" . $_GET["match_num"] . "\"/></td></tr> 
		<tr><td>Week number: </td><td><input type=\"number\" min=\"0\" max=\"52\" name=\"week_num\" value=\"" . $_GET["week_num"] . "\"/></td></tr>
		<tr><td>Owner server: </td><td><input type=\"text\" name=\"obj_owner\" value=\"" . $_GET["obj_owner"] . "\"/></td></tr>
		<tr><td>Owner color: </td><td><select name=\"owner_color\">";
		generate_option("","","owner_color");
		generate_option("Green","Green","owner_color");
		generate_option("Blue","Blue","owner_color");
		generate_option("Red","Red","owner_color");
		echo "</select></td></tr>
		<tr><td>Last seized: </td><td><input type=\"datetime\" placeholder=\"YYYY-MM-DD hh:mm:ss\" name=\"last_flipped_begin\" value=\"" . $_GET["last_flipped_begin"] . "\"/></td>
			<td>-</td><td><input type=\"datetime\" placeholder=\"YYYY-MM-DD hh:mm:ss\" name=\"last_flipped_end\" value=\"" . $_GET["last_flipped_end"] . "\"/></td></tr>
		<tr><td>Claimed at: </td><td><input type=\"datetime\" name=\"claimed_at_begin\" value=\"" . $_GET["claimed_at_begin"] . "\"/></td><td>-</td>
			<td><input type=\"datetime\" name=\"claimed_at_end\" value=\"" . $_GET["claimed_at_end"] . "\"/></td></tr>
		<tr><td>In-game clock time: </td><td><input type=\"number\" min=\"1\" max=\"15\" name=\"tick_timer_begin\" value=\"" . $_GET["tick_timer_begin"] . "\"/></td>
			<td>-</td><td><input type=\"number\"min=\"1\" max=\"15\" name=\"tick_timer_end\" value=\"" . $_GET["tick_timer_end"] . "\"/></td></tr>
		<tr><td>Objective name: </td><td><input type=\"text\" name=\"obj_name\" value=\"" . $_GET["obj_name"] . "\"/></td></tr>
		<tr><td>Objective type: </td><td><select name=\"obj_type\">";
		generate_option("","","obj_type");
		generate_option("Camp","Camp","obj_type");
		generate_option("Tower","Tower","obj_type");
		generate_option("Keep","Keep","obj_type");
		generate_option("Castle","Castle","obj_type");
		echo "</select></td></tr>
		<tr><td>Map type: </td><td><select name=\"map_type\">";
		generate_option("","All Maps","map_type");
		generate_option("center","Eternal Battlegrounds","map_type");
		generate_option("greenHome","Green Borderlands","map_type");
		generate_option("blueHome","Blue Borderlands","map_type");
		generate_option("redHome","Red Borderlands","map_type");
		generate_option("home","All Borderlands","map_type");
		echo "</select></td></tr>
		<tr><td>Guild name: </td><td><input type=\"text\" name=\"guild_name\" value=\"" . $_GET["guild_name"] . "\"/></td></tr>
		<tr><td>Guild tag: </td><td><input type=\"text\" name=\"guild_tag\" value=\"" . $_GET["guild_tag"] . "\"/></td></tr>
		<tr><td>Page #:</td><td><input type=\"number\" min=\"0\" name=\"offset_num\" value=\"" . $_GET["offset_num"] . "\"/></td></tr>";
	echo "</table></div>
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
		$offset_amount = 500;
		$limit = 50000;
		$activityQuery = "SELECT timeStamp, duration_owned as \"Duration Owned\", duration_claimed as \"Duration Claimed\", activity_data.match_id as \"Match ID\", week_num as \"Week Number\",
		server_info.name as \"Server\", owner_color as \"Color\", last_flipped as \"Last Seized At\",
		claimed_at as \"Claimed At\", tick_timer as \"Ingame Clock\", objective.name as \"Objective Name\",
		type as \"Objective Type\", compass_direction as \"Cardinal Direction\", map_type as \"Map\",
		guild_name as \"Guild Name\", guild_tag as \"Guild Tag\"
		FROM activity_data
		INNER JOIN guild ON activity_data.guild_id=guild.guild_id
		INNER JOIN objective ON activity_data.obj_id=objective.obj_id
		INNER JOIN server_info on activity_data.owner_server=server_info.srv_id
		INNER JOIN match_details on match_details.match_id=activity_data.match_id
		WHERE activity_data.start_time = match_details.start_time "; //partially a spoof WHERE clause; also required to properly link activity_data and match_details
		if (
			$_GET["match_num"] == "" and $_GET["week_num"] == "" and $_GET["obj_owner"] == ""
			and $_GET["owner_color"] == "" and $_GET["last_flipped_begin"] == "" and $_GET["last_flipped_end"] == ""
			and $_GET["claimed_at_begin"] == "" and $_GET["claimed_at_end"] == "" and $_GET["tick_timer_begin"] == ""
			and $_GET["tick_timer_end"] == "" and $_GET["obj_name"] == "" and $_GET["obj_type"] == ""
			and $_GET["map_type"] == "" and $_GET["guild_name"] == "" and $_GET["guild_tag"] == ""
			and $_GET["timeStamp_begin"] == "" and $_GET["timeStamp_end"] == "" and $_GET["region"] == ""
		)
		{
			die(""); //if the user did not enter any search criteria, stop early
		}
		check_inputs();
		if ($_GET["timeStamp_begin"] != "")
		{
			$activityQuery .= "and timeStamp >= \"" . $_GET["timeStamp_begin"] . "\" ";
		}
		if ($_GET["timeStamp_end"] != "")
		{
			$activityQuery .= "and timeStamp <= \"" . $_GET["timeStamp_end"] . "\" ";
		}
		if ($_GET["region"] != "")
		{
			$activityQuery .= "and activity_data.match_id LIKE \"" . $_GET["region"] . "-%\" ";
		}
		if ($_GET["match_num"] != "")
		{
			$activityQuery .= "and activity_data.match_id LIKE \"%-" . $_GET["match_num"] . "\" ";
		}
		if ($_GET["week_num"] != "")
		{
			$activityQuery .= "and week_num = \"" . $_GET["week_num"] . "\" ";
		}
		if ($_GET["obj_owner"] != "")
		{
			if (strlen($_GET["obj_owner"]) < 5)
			{
				$activityQuery .= "and server_info.shortName = \"" . $_GET["obj_owner"] . "\" ";
			}
			else
			{
				$activityQuery .=  "and server_info.name LIKE \"%" . $_GET["obj_owner"] . "%\" ";
			}
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
		$activityQuery .= "ORDER BY " . $_GET["sort_by1"] . "," . $_GET["sort_by2"] . " LIMIT $limit OFFSET " . $_GET["offset_num"]*$offset_amount . ";";
		//
		//
		//		
		$resultSet = $conn->query($activityQuery);
		if ($resultSet->rowCount() == 0) die("<b>No data returned.</b>");
		echo "<div class=\"container-fluid col-sm-12\"><table class=\"table-striped table-bordered table-hover text-center\">";
		echo "<th>Row #</th><th>Time Stamp</th><th>Match ID</th><th>Week Number</th><th>Server</th><th>Color</th>
		<th>Last Seized At</th><th>Duration Owned</th><th>Claimed At</th><th>Duration Claimed</th><th>Ingame Clock</th><th>Objective Name</th>
		<th>Objective Type</th><th>Map</th><th>Guild Name</th><th>Guild Tag</th>";
		$i = 0;
		foreach ($resultSet as $row)
		{
			$i++;
			if ($i < $offset_amount + 1)
			{
			    echo "<tr>";
			    echo "<td>" . $i . "</td>";
			    echo "<td>" . $row["timeStamp"] . "</td>";
			    echo "<td>" . $row["Match ID"] . "</td>";
			    echo "<td>" . $row["Week Number"] . "</td>";
			    echo "<td>" . $row["Server"] . "</td>";
			    echo "<td bgcolor=\"";
			    if ($row["Color"] == "Green")
			    {
			    	echo "#00cc00";
			    }
			    else if ($row["Color"] == "Blue")
			    {
			    	echo "#3399ff";
			    }
			    else if ($row["Color"] == "Red")
			    {
			    	echo "#ff5050";
			    }
			    echo "\">" . $row["Color"] . "</td>";
			    echo "<td>" . $row["Last Seized At"] . "</td>";
			    echo "<td>" . $row["Duration Owned"] . "</td>";
			    echo "<td>" . $row["Claimed At"] . "</td>";
			    echo "<td>" . $row["Duration Claimed"] . "</td>";
			    echo "<td>" . $row["Ingame Clock"] . "</td>";
			    echo "<td>" . $row["Objective Name"] . "</td>";
			    echo "<td>" . $row["Cardinal Direction"] . " " . $row["Objective Type"] . "</td>";
			    echo "<td bgcolor=\"";
			    if ($row["Map"] == "GreenHome")
			    {
			    	echo "#00cc00";
			    }
			    else if ($row["Map"] == "BlueHome")
			    {
			    	echo "#3399ff";
			    }
			    else if ($row["Map"] == "RedHome")
			    {
			    	echo "#ff5050";
			    }
			    else
			    {
			    	echo "yellow";
			    }
			    echo "\">" . $row["Map"] . "</td>";
			    echo "<td>" . $row["Guild Name"] . "</td>";
			    echo "<td>" . $row["Guild Tag"] . "</td>";
			    echo "</tr>";
			}
		}
		if ($i == $limit)
		{
			echo "<b>Maximum number of records obtained.<br> Refine search parameters or change page number to view more results.</b><br>";
		}
		echo "Displaying results " . $_GET["offset_num"]*$offset_amount . "-" 
		. ($_GET["offset_num"]+1)*$offset_amount . " out of " 
		. ($i + ($_GET["offset_num"])*$offset_amount) . ".<p>";
		echo "</table></div>";
		echo "<p>Displaying results " . $_GET["offset_num"]*$offset_amount . "-" 
		. ($_GET["offset_num"]+1)*$offset_amount . " out of " 
		. ($i + ($_GET["offset_num"])*$offset_amount) . ".</p>";
		?>
	</body>
</html>
