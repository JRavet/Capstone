<?php
include 'analyser_header.php';
include 'bootstrap_styling.php';
?>
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
			$temp[] = array('v' => (string) $r);
			foreach ($varNames as $v)
			{
				$temp[] = array('v' => (int) $r);
			}
			$rows[] = array('c' => $temp);
	    }
		$table['rows'] = $rows;
		return $jsonTable = json_encode($table);
	}
	function generate_googleChart($data,$title,$idName,$options,$x,$y,$obj_type)
	{
		$size=60;
		if ($obj_type == "Castle")
		{
			$size*=2;
		}
		elseif ($obj_type == "Keep")
		{
			$size*=1.66;
		}
		elseif ($obj_type == "Tower")
		{
			$size*=1.33;
		}
	echo "<script type=\"text/javascript\">
	google.load('visualization', '1', {'packages':['corechart']});
	google.setOnLoadCallback(drawChart);
	function drawChart()
	{
		var data = new google.visualization.DataTable($data);
		var options = {
			title: \"$title\",
			width: $size,
			legend: {position: \"none\"},
			backgroundColor: 'transparent',
			$options
			height: $size,
			tooltip:{textStyle:{fontSize:($size/60)*8-1}},
			titleTextStyle:
			{
				color:'white',
				fontSize:8
			},
			colors: ['#00cc00','#3399ff','#ff5050']
		};
		var div = document.getElementById(\"$idName\");
		div.style.position=\"absolute\";
		div.style.left=(($x/5.5))-975-$size/2+45+'px'; //5.5, -965
		div.style.top=(($y/5.5))-1030-$size/2+45+'px'; //5.5, -1160
		new google.visualization.PieChart(div).draw(data,options);
	}
	</script>
	<div id=\"$idName\"></div>";
	}
	function generate_radioButton($name,$text,$value)
	{
		echo "<td><input type=\"radio\" name=\"$name\" value=\"$value\"";
		if ($_GET[$name] == $value)
		{
			echo " checked ";
		}
		echo ">$text</td>";
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
	<table class=\"table-condensed\">"; 
		createDateTime("Time stamp", "timeStamp_begin", "timeStamp_end");
		echo "<tr><td>Region / Match Number:</td><td> <select name=\"region\">";
		generate_option("","","region");
		generate_option("1","1 (NA)","region");
		generate_option("2","2 (EU)","region");
		echo "</select><input type=\"number\" min=\"1\" max=\"9\" name=\"match_num\" value=\"" . $_GET["match_num"] . "\"/></td></tr> 
		<tr><td>Week number: </td><td><input type=\"number\" min=\"0\" max=\"52\" name=\"week_num\" value=\"" . $_GET["week_num"] . "\"/></td></tr>
		<tr><td>Owner server: </td><td><input type=\"text\" name=\"obj_owner\" value=\"" . $_GET["obj_owner"] . "\"/></td></tr>";
		createDateTime("Last seized", "last_flipped_begin", "last_flipped_end");
		createDateTime("Claimed at", "claimed_at_begin", "claimed_at_end");
		echo "<tr><td>In-game clock time: </td><td><input type=\"number\" min=\"1\" max=\"15\" name=\"tick_timer_begin\" value=\"" . $_GET["tick_timer_begin"] . "\"/></td>
			<td>-</td><td><input type=\"number\"min=\"1\" max=\"15\" name=\"tick_timer_end\" value=\"" . $_GET["tick_timer_end"] . "\"/></td></tr>
		<tr><td>Guild name: </td><td><input type=\"text\" name=\"guild_name\" value=\"" . $_GET["guild_name"] . "\"/></td></tr>
		<tr><td>Guild tag: </td><td><input type=\"text\" name=\"guild_tag\" value=\"" . $_GET["guild_tag"] . "\"/></td></tr>
		</table><table class=\"table-condensed\"><tr>"; 
		generate_radioButton("data_shown","Activity Count",0);
		generate_radioButton("data_shown","Claims Count",1);
		generate_radioButton("data_shown","Duration-owned (seconds)",2);
		generate_radioButton("data_shown","Duration-claimed (seconds)",3);
	echo "</tr></table>
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
		if ($_GET["data_shown"] == 0 or $_GET["data_shown"] == 1)
		{
			$selectAdditions = "SUM(CASE WHEN owner_color=\"Green\" THEN 1 ELSE 0 END) as \"Green Count\",
SUM(CASE WHEN owner_color=\"Blue\" THEN 1 ELSE 0 END) as \"Blue Count\",
SUM(CASE WHEN owner_color=\"Red\" THEN 1 ELSE 0 END) as \"Red Count\",";
		}
		if ($_GET["data_shown"] == 1)
		{
			$whereAdditions = " and claimed_at > 0 ";
		}
		if ($_GET["data_shown"] == 2)
		{
			$selectAdditions = "SUM(CASE WHEN owner_color=\"Green\" THEN TIME_TO_SEC(duration_owned) ELSE 0 END) as \"Green Count\",
SUM(CASE WHEN owner_color=\"Blue\" THEN TIME_TO_SEC(duration_owned) ELSE 0 END) as \"Blue Count\",
SUM(CASE WHEN owner_color=\"Red\" THEN TIME_TO_SEC(duration_owned) ELSE 0 END) as \"Red Count\",";
		}
		if ($_GET["data_shown"] == 3)
		{
			$selectAdditions = "SUM(CASE WHEN owner_color=\"Green\" THEN TIME_TO_SEC(duration_claimed) ELSE 0 END) as \"Green Count\",
SUM(CASE WHEN owner_color=\"Blue\" THEN TIME_TO_SEC(duration_claimed) ELSE 0 END) as \"Blue Count\",
SUM(CASE WHEN owner_color=\"Red\" THEN TIME_TO_SEC(duration_claimed) ELSE 0 END) as \"Red Count\",";
			$whereAdditions = " and claimed_at > 0 ";
		}
		$activityQuery = "SELECT 
$selectAdditions
activity_data.obj_id as \"obj_id\",
objective.name as \"Objective Name\",
objective.type as \"obj_type\",
coordx, coordy
FROM activity_data 
INNER JOIN guild ON activity_data.guild_id=guild.guild_id
INNER JOIN objective ON activity_data.obj_id=objective.obj_id
INNER JOIN server_info on activity_data.owner_server=server_info.srv_id
INNER JOIN match_details on match_details.match_id=activity_data.match_id
WHERE activity_data.start_time = match_details.start_time $whereAdditions";
		if (
			$_GET["match_num"] == "" and $_GET["week_num"] == "" and $_GET["obj_owner"] == ""
			and $_GET["owner_color"] == "" and $_GET["last_flipped_begin"] == "" and $_GET["last_flipped_end"] == ""
			and $_GET["claimed_at_begin"] == "" and $_GET["claimed_at_end"] == "" and $_GET["tick_timer_begin"] == ""
			and $_GET["tick_timer_end"] == "" and $_GET["guild_name"] == "" and $_GET["guild_tag"] == ""
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
		$resultSet = $conn->query($activityQuery);
		if ($resultSet->rowCount() == 0) die("<b>No data returned.</b>");
		echo "<img src=\"gw2map.jpg\" style=\"width:2048;height:1490;position:absolute;z-index:-1;\">";
		foreach ($resultSet as $r)
		{
			generate_googleChart(generate_jsontable($resultSet,array("Green Count", "Blue Count", "Red Count"),array($r["Green Count"],$r["Blue Count"],$r["Red Count"])),$r["Objective Name"],$r["Objective Name"],"",$r["coordx"],$r["coordy"],$r["obj_type"]);
		}
		?>
	</body>
</html>
