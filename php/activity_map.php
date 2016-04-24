<?php include 'analyser_header.php'; ?>
<?php
	function generate_jsontable($resultSet,$varNames, $varData)
	{
		$rows = array();
		$table = array();
		$table['cols'] = array(array('label' => 'obj_id', 'type' => 'string'));
		foreach ($varNames as $v)
		{
			array_push($table['cols'],array('label' => $v, 'type' => 'number'));
		}
	    foreach($varData as $r)
	    {
			$temp = array();
			$temp[] = array('v' => (string) $r['obj_id']);
			foreach ($varNames as $v)
			{
				$temp[] = array('v' => (int) $r["$v"]);
			}
			$rows[] = array('c' => $temp);
	    }
		$table['rows'] = $rows;
		return $jsonTable = json_encode($table);
	}
	function generate_googleChart($data,$title,$idName,$options,$x,$y)
	{
	echo "<script type=\"text/javascript\">
	google.load('visualization', '1', {'packages':['corechart']});
	google.setOnLoadCallback(drawChart);
	function drawChart()
	{
		var data = new google.visualization.DataTable($data);
		var options = {
			title: \"$title\",
			width: 120,
			backgroundColor: 'transparent',
			$options
			height: 120,
			colors: ['#00cc00','#3399ff','#ff5050']
		};
		var div = document.getElementById(\"$idName\");
		div.style.position=\"absolute\";
		div.style.left=(($x/5.5))-970+'px';
		div.style.top=(($y/5.5))-1100+'px';
		new google.visualization.PieChart(div).draw(data,options);
	}
	</script>
	<div id=\"$idName\"></div>";
	}
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<html>
	<title> Activity Map </title>
	<style>body{background:#FFF;}</style>
	<body>
	<?php
	echo "<form action=\"activity_map.php\" method=\"GET\">
	<table> <tr><td>Time Stamp: </td><td><input type=\"datetime\" name=\"timeStamp_begin\" value=\"" . $_GET["timeStamp_begin"] . "\"/></td>
			<td>-</td><td><input type=\"datetime\" name=\"timeStamp_end\" value=\"" . $_GET["timeStamp_end"] . "\"/></td></tr>
		<tr><td>Region / Match Number:</td><td> <select name=\"region\">";
		generate_option("","","region");
		generate_option("1","NA","region");
		generate_option("2","EU","region");
		echo "</select><input type=\"number\" min=\"1\" max=\"9\" name=\"match_num\" value=\"" . $_GET["match_num"] . "\"/></td></tr> 
		<tr><td>Week number: </td><td><input type=\"number\" min=\"0\" max=\"52\" name=\"week_num\" value=\"" . $_GET["week_num"] . "\"/></td></tr>
		<tr><td>Owner server: </td><td><input type=\"text\" name=\"obj_owner\" value=\"" . $_GET["obj_owner"] . "\"/></td></tr>
		<tr><td>Owner color: </td><td><select name=\"owner_color\">";
		generate_option("","","owner_color");
		generate_option("Green","Green","owner_color");
		generate_option("Blue","Blue","owner_color");
		generate_option("Red","Red","owner_color");
		echo "</select></td></tr>
		<tr><td>Last seized: </td><td><input type=\"datetime\" name=\"last_flipped_begin\" value=\"" . $_GET["last_flipped_begin"] . "\"/></td>
			<td>-</td><td><input type=\"datetime\" name=\"last_flipped_end\" value=\"" . $_GET["last_flipped_end"] . "\"/></td></tr>
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
		<tr><td>Guild tag: </td><td><input type=\"text\" name=\"guild_tag\" value=\"" . $_GET["guild_tag"] . "\"/></td></tr>";
	echo "</table>
	<table>
	<tr>
	<td><input type=\"submit\" value=\"Submit Query\"/></td><td style=\"width:175px\"></td>
	</form></td>
	<td><form action=\"activity_map.php\">
		<input type=\"submit\" value=\"Reset fields\"/>
	</form></td>
	</tr>
	</table>";
	?>
	<br/>
	<?php
		$activityQuery = "SELECT 
SUM(CASE WHEN owner_color=\"Green\" THEN 1 ELSE 0 END) as \"Green Count\",
SUM(CASE WHEN owner_color=\"Blue\" THEN 1 ELSE 0 END) as \"Blue Count\",
SUM(CASE WHEN owner_color=\"Red\" THEN 1 ELSE 0 END) as \"Red Count\",
activity_data.obj_id as \"obj_id\",
objective.name as \"Objective Name\",
coordx, coordy,
map_type as \"Map\"
FROM activity_data 
INNER JOIN guild ON activity_data.guild_id=guild.guild_id
INNER JOIN objective ON activity_data.obj_id=objective.obj_id
INNER JOIN server_info on activity_data.owner_server=server_info.srv_id
INNER JOIN match_details on match_details.match_id=activity_data.match_id
WHERE activity_data.start_time = match_details.start_time ";
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
			if (strlen($_GET["obj_owner"]) < 4)
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
		$activityQuery .= "GROUP BY objective.obj_id;";
		//
		//
		//
		echo "<img src=\"gw2map.jpg\" style=\"width:2048;height:1490;position:absolute;z-index:-1;\">";
		$resultSet = $conn->query($activityQuery);
		foreach ($resultSet as $r)
		{
			generate_googleChart(generate_jsontable($resultSet,array("Green Count", "Blue Count", "Red Count"),array($r["Green Count"],$r["Blue Count"],$r["Red Count"])),$r["Objective Name"],$r["Objective Name"],"",$r["coordx"],$r["coordy"]);
		}
		?>
	</body>
</html>