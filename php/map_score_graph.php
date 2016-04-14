<?php
	include 'analyser_header.php';
	
?>
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
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<html>
	<title> Map Score Analyser </title>
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
		generate_option("1","NA","region"); 
		generate_option("2","EU","region");
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
			sum(error_corrected) as \"Errors Corrected\"
			FROM map_scores 
			INNER JOIN match_details ON map_scores.match_id = match_details.match_id
			WHERE match_details.start_time = map_scores.start_time ";
		if (
			$_GET["match_num"] == "" and $_GET["week_num"] == "" and $_GET["timeStamp_begin"] == ""
			and $_GET["timeStamp_end"] == "" and $_GET["map_type"] == "" and $_GET["region"] == ""
		)
		{
			die(""); //if the user did not enter any search criteria, stop early
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
		if ($_GET["last_flipped_end"] != "")
		{
			$scoreQuery .= "and timeStamp <= \"" . $_GET["timeStamp_end"] . "\" ";
		}
		$scoreQuery .= " GROUP BY timeStamp, map_scores.match_id ORDER BY " . $_GET["sort_by"] . " LIMIT 18446744073709551615 OFFSET " . $_GET["offset_num"]*$offset_amount . ";";
		//
		//
		//
		$resultSet = $conn->query($scoreQuery);
		$resultSet = new ArrayIterator($resultSet->fetchAll());
		$pptTable = generate_jsontable($resultSet,array("Green PPT","Blue PPT","Red PPT"));
		$killsTable = generate_jsontable($resultSet,array("Green Kills","Blue Kills","Red Kills"));
    ?>
	<script type="text/javascript">
		google.load('visualization', '1', {'packages':['corechart']});
		google.setOnLoadCallback(drawChart);
		function drawChart() 
		{
			var data = new google.visualization.DataTable(<?=$pptTable?>);
			var options = {
				title: 'PPT Chart',
				curve_type: 'function',
				width: 800,
				height: 600
			};
			new google.visualization.LineChart(document.getElementById('ppt_chart')).draw(data,options);
		}
	</script>
	<div id="ppt_chart"></div>
	<script type="text/javascript">
		google.load('visualization', '1', {'packages':['corechart']});
		google.setOnLoadCallback(drawChart);
		function drawChart() 
		{
			var data = new google.visualization.DataTable(<?=$killsTable?>);
			var options = {
				title: 'Kills Chart',
				curve_type: 'function',
				width: 800,
				height: 600
			};
			new google.visualization.LineChart(document.getElementById('kills_chart')).draw(data,options);
		}
	</script>
	<div id="kills_chart"></div>
	</body>
</html>
