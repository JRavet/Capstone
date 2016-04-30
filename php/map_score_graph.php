<?php include 'analyser_header.php'; ?>
<?php
	function generate_jsontable($resultSet,$varNames)
	{
		$rows = array();
		$table = array();
		$table['cols'] = array(array('label' => 'Time Stamp', 'type' => 'string'));
		foreach ($varNames as $v)
		{
			array_push($table['cols'],array('label' => $v, 'type' => 'number'));
		}
	    foreach($resultSet as $r)
	    {
			$temp = array();
			$temp[] = array('v' => (string) $r['Time Stamp']);
			foreach ($varNames as $v)
			{
				$temp[] = array('v' => (int) $r["$v"]);
			}
			$rows[] = array('c' => $temp);
	    }
		$table['rows'] = $rows;
		return $jsonTable = json_encode($table);
	}
	function generate_googleChart($data,$title,$idName,$options)
	{
	echo "<script type=\"text/javascript\">
	google.load('visualization', '1', {'packages':['corechart']});
	google.setOnLoadCallback(drawChart);
	function drawChart()
	{
		var data = new google.visualization.DataTable($data);
		var options = {
			title: \"$title\",
			width: 600,
			$options
			height: 300,
			hAxis: {
				textPosition: 'none'
			},
			pointSize:1.5,
			legend: {position: 'bottom'},
			colors: ['#00cc00','#3399ff','#ff5050']
		};
		new google.visualization.LineChart(document.getElementById('$idName')).draw(data,options);
	}
	</script>
	<div id=\"$idName\"></div>";
	}
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<html>
	<title> Map Score Analyser </title>
	<style>body{background:#FFF;}</style>
	<body>
	<?php
	echo "<form action=\"map_score_graph.php\" method=\"GET\">
	<table>";
	echo "<tr><td>Sort by:</td><td><select name=\"sort_by\">";
		generate_option("week_num,timeStamp,map_scores.match_id","Week Number, Time Stamp, Match ID","sort_by");
		generate_option("week_num,map_scores.match_id,timeStamp","Week Number, Match ID, Time Stamp","sort_by");
		generate_option("timeStamp","Time Stamp","sort_by");
		generate_option("map_scores.match_id","Match ID","sort_by");
		generate_option("week_num","Week Number","sort_by");
	echo "</select></tr>
		<tr><td>Region / Match Number:</td><td> <select name=\"region\">";
		generate_option("","","region");
		generate_option("1","1 (NA)","region"); 
		generate_option("2","2 (EU)","region");
		echo "</select><input type=\"number\" min=\"1\" max=\"9\" name=\"match_num\" value=\"" . $_GET["match_num"] . "\"/></td></tr> 
		<tr><td>Week number: </td><td><input type=\"number\" min=\"0\" max=\"52\" name=\"week_num\" value=\"" . $_GET["week_num"] . "\"/></td></tr>
		<tr><td>Time stamp: </td><td><input type=\"datetime\" name=\"timeStamp_begin\" value=\"" . $_GET["timeStamp_begin"] . "\"/></td>
			<td>-</td><td><input type=\"datetime\" name=\"timeStamp_end\" value=\"" . $_GET["timeStamp_end"] . "\"/></td></tr>
		<tr><td>Map type: </td><td><select name=\"map_type\">";
		generate_option("","All Maps","map_type");
		generate_option("center","Eternal Battlegrounds","map_type");
		generate_option("greenHome","Green Borderlands","map_type");
		generate_option("blueHome","Blue Borderlands","map_type");
		generate_option("redHome","Red Borderlands","map_type");
		generate_option("home","All Borderlands","map_type");
		echo "</select></td></tr>
		<tr><td>Page #:</td><td><input type=\"number\" min=\"0\" name=\"offset_num\" value=\"" . $_GET["offset_num"] . "\"/></td></tr>";
	echo "</table>
	<table>
	<tr>
	<td><input type=\"submit\" value=\"Submit Query\"/></td><td style=\"width:175px\"></td>
	</form></td>
	<td><form action=\"map_score_graph.php\">
		<input type=\"submit\" value=\"Reset fields\"/>
	</form></td>
	</tr>
	</table>";
	?>
	<br/>
	<?php
		$scoreQuery = "SELECT map_scores.match_id as \"Match ID\", week_num as \"Week Number\",
			timeStamp as \"Time Stamp\", sum(greenDeaths) as \"Green Deaths\",
			sum(blueDeaths) as \"Blue Deaths\", sum(redDeaths) as \"Red Deaths\",
			sum(greenKills) as \"Green Kills\", sum(blueKills) as \"Blue Kills\",
			sum(redKills) as \"Red Kills\", sum(greenScore) as \"Green Score\",
			sum(blueScore) as \"Blue Score\", sum(redScore) as \"Red Score\",
			sum(green_ppt) as \"Green PPT\", sum(blue_ppt) as \"Blue PPT\",
			sum(red_ppt) as \"Red PPT\", match_details.green_srv as \"Green Server\",
			match_details.blue_srv as \"Blue Server\", match_details.red_srv as \"Red Server\",
			sum(error_corrected) as \"Errors Corrected\",
			sum(greenKills)/sum(greenDeaths)*100.0 as \"Green KD%\",
			sum(blueKills)/sum(blueDeaths)*100.0 as \"Blue KD%\",
			sum(redKills)/sum(redDeaths)*100.0 as \"Red KD%\"
			FROM map_scores 
			INNER JOIN match_details ON map_scores.match_id = match_details.match_id
			WHERE match_details.start_time = map_scores.start_time ";
		if (
			$_GET["match_num"] == "" or $_GET["week_num"] == "" or $_GET["region"] == ""
		)
		{
			die("<b>Please specify a region, match number and week number.</eb>"); //if the user did not enter any search criteria, stop early
		}
		check_inputs();
		if ($_GET["region"] != "")
		{
			$scoreQuery .= "and map_scores.match_id LIKE \"" . $_GET["region"] . "-%\" ";
		}
		if ($_GET["match_num"] != "")
		{
			$scoreQuery .= "and map_scores.match_id LIKE \"%-" . $_GET["match_num"] . "\" ";
		}
		if ($_GET["week_num"] != "")
		{
			$scoreQuery .= "and week_num = \"" . $_GET["week_num"] . "\" ";
		}
		if ($_GET["map_type"] != "")
		{
			$scoreQuery .= "and map_id LIKE \"%" . $_GET["map_type"] . "%\" ";
		}
		if ($_GET["timeStamp_begin"] != "")
		{
			$scoreQuery .= "and timeStamp >= \"" . $_GET["timeStamp_begin"] . "\" ";
		}
		if ($_GET["timeStamp_end"] != "")
		{
			$scoreQuery .= "and timeStamp <= \"" . $_GET["timeStamp_end"] . "\" ";
		}
		$scoreQuery .= " GROUP BY timeStamp, map_scores.match_id ORDER BY " . $_GET["sort_by"] . " LIMIT 18446744073709551615 OFFSET " . $_GET["offset_num"]*$offset_amount . ";";
		//
		//
		//
		$resultSet = $conn->query($scoreQuery);
		if ($resultSet->rowCount() == 0) die("<b>No data returned.</b>");
		$resultSet = new ArrayIterator($resultSet->fetchAll());
		$redName = $conn->query("SELECT name FROM server_info INNER JOIN match_details ON red_srv=srv_id WHERE match_id = \"" . $_GET["region"] . "-" . $_GET["match_num"] . "\" AND week_num = \"" . $_GET["week_num"] . "\";");
		$blueName = $conn->query("SELECT name FROM server_info INNER JOIN match_details ON blue_srv=srv_id WHERE match_id = \"" . $_GET["region"] . "-" . $_GET["match_num"] . "\" AND week_num = \"" . $_GET["week_num"] . "\";");
		$greenName = $conn->query("SELECT name FROM server_info INNER JOIN match_details ON green_srv=srv_id WHERE match_id = \"" . $_GET["region"] . "-" . $_GET["match_num"] . "\" AND week_num = \"" . $_GET["week_num"] . "\";");
		echo "<center><table border=\"1\"><tr><td style=\"padding:8px\" bgcolor=\"#00cc00\">" . $greenName->fetchColumn() . "</td><td>|</td><td style=\"padding:8px\" bgcolor=\"#3399ff\">". $blueName->fetchColumn() . "</td><td>|</td><td style=\"padding:8px\" bgcolor=\"#ff5050\">" . $redName->fetchColumn() . "</td></tr></table></center>";
		echo "<table><tr><td>";
		generate_googleChart(generate_jsontable($resultSet,array("Green PPT","Blue PPT","Red PPT")),"PPT","ppt_chart");
		echo "</td><td>";
		generate_googleChart(generate_jsontable($resultSet,array("Green Score","Blue Score","Red Score")),"Scores","score_chart");
		echo "</td></tr><tr><td>";
		generate_googleChart(generate_jsontable($resultSet,array("Green Kills","Blue Kills","Red Kills")),"Kills","kills_chart");
		echo "</td><td>";
		generate_googleChart(generate_jsontable($resultSet,array("Green Deaths","Blue Deaths","Red Deaths")),"Deaths","deaths_chart");
		echo "</td></tr><tr><td>";
		generate_googleChart(generate_jsontable($resultSet,array("Green KD%","Blue KD%","Red KD%")),"KD Ratio","kd_chart");
		?>
	</body>
</html>
