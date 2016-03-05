<?php include 'analyser_header.php'; ?>
<html>
	<title> Map Score Analyser </title>
	<body>
	<?php
	echo "<form action=\"map_score_analyser.php\" method=\"GET\">
	<table>";
	echo "<tr><td>Sort by:</td><td><select name=\"sort_by\">";
		generate_option("timeStamp","Time Stamp","sort_by");
		generate_option("map_scores.match_id","Match ID","sort_by");
		generate_option("week_num","Week Number","sort_by");
	echo "</select></tr>
		<tr><td>Match ID: </td><td><input type=\"text\" name=\"match_id\" value=\"" . $_GET["match_id"] . "\"/></td></tr> 
		<tr><td>Week number: </td><td><input type=\"number\" min=\"0\" max=\"52\" name=\"week_num\" value=\"" . $_GET["week_num"] . "\"/></td></tr>
		<tr><td>Time stamp: </td><td><input type=\"datetime\" name=\"timeStamp_begin\" value=\"" . $_GET["timeStamp_begin"] . "\"/></td>
			<td>-</td><td><input type=\"datetime\" name=\"timeStamp_end\" value=\"" . $_GET["timeStamp_end"] . "\"/></td></tr>
		<tr><td>Map Type: </td><td><input type=\"text\" name=\"map_type\" value=\"" . $_GET["map_type"] . "\"/></td></tr>
		<tr><td>Page #:</td><td><input type=\"number\" min=\"0\" name=\"offset_num\" value=\"" . $_GET["offset_num"] . "\"/></td></tr>";
	echo "</table>
	<table>
	<tr>
	<td><input type=\"submit\" value=\"Submit Query\"/></td><td style=\"width:175px\"></td>
	</form></td>
	<td><form action=\"map_score_analyser.php\">
		<input type=\"submit\" value=\"Reset fields\"/>
	</form></td>
	</tr>
	</table>";
	?>
	<br/>
	<?php
		$offset_amount = 500;
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
			$_GET["match_id"] == "" and $_GET["week_num"] == "" and $_GET["timeStamp_begin"] == ""
			and $_GET["timeStamp_end"] == "" and $_GET["map_type"] == ""
		)
		{
			die(""); //if the user did not enter any search criteria, stop early
		}
		check_inputs();
		if ($_GET["match_id"] != "")
		{
			$scoreQuery .= "and map_scores.match_id = \"" . $_GET["match_id"] . "\" ";
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
			$scoreQuery .= "and timeStamp_begin >= \"" . $_GET["timeStamp_begin"] . "\" ";
		}
		if ($_GET["last_flipped_end"] != "")
		{
			$scoreQuery .= "and timeStamp_end <= \"" . $_GET["timeStamp_end"] . "\" ";
		}
		$scoreQuery .= " GROUP BY timeStamp, map_scores.match_id ORDER BY " . $_GET["sort_by"] . " LIMIT 18446744073709551615 OFFSET " . $_GET["offset_num"]*$offset_amount . ";";
		//
		//
		//
		echo "<table border=\"1\">";
		echo "<th>Row #</th><th>Match ID</th><th>Week Number</th><th>Time Stamp</th><th>Green Deaths</th>
		<th>Blue Deaths</th><th>Red Deaths</th><th>Green Kills</th><th>Blue Kills</th><th>Red Kills</th>
		<th>Green Score</th><th>Blue Score</th><th>Red Score</th><th>Green PPT</th><th>Blue PPT</th>
		<th>Red PPT</th><th>Green Server</th><th>Blue Server</th><th>Red Server</th><th>Errors Corrected</th>";
		$i = 0;
		$resultSet = $conn->query($scoreQuery);
		foreach ($resultSet as $row)
		{
			$i++;
			if ($i < $offset_amount + 1)
			{
			    echo "<tr>";
			    echo "<td>" . $i . "</td>";
			    echo "<td>" . $row["Match ID"] . "</td>";
			    echo "<td>" . $row["Week Number"] . "</td>";
			    echo "<td>" . $row["Time Stamp"] . "</td>";
			    echo "<td>" . $row["Green Deaths"] . "</td>";
			    echo "<td>" . $row["Blue Deaths"] . "</td>";
			    echo "<td>" . $row["Red Deaths"] . "</td>";
			    echo "<td>" . $row["Green Kills"] . "</td>";
			    echo "<td>" . $row["Blue Kills"] . "</td>";
			    echo "<td>" . $row["Red Kills"] . "</td>";
			    echo "<td>" . $row["Green Score"] . "</td>";
			    echo "<td>" . $row["Blue Score"] . "</td>";
			    echo "<td>" . $row["Red Score"] . "</td>";
			    echo "<td>" . $row["Green PPT"] . "</td>";
			    echo "<td>" . $row["Blue PPT"] . "</td>";
			    echo "<td>" . $row["Red PPT"] . "</td>";
			    echo "<td>" . $row["Green Server"] . "</td>";
			    echo "<td>" . $row["Blue Server"] . "</td>";
			    echo "<td>" . $row["Red Server"] . "</td>";
			    echo "<td>" . $row["Errors Corrected"] . "</td>";
			    echo "</tr>";
			}
		}
		if ($i == 0 and $_GET["offset_num"] > 0)
		{
			die("Page number too high; data out of range.<p>");
		}
		echo "Displaying results " . $_GET["offset_num"]*$offset_amount . "-" 
		. ($_GET["offset_num"]+1)*$offset_amount . " out of " 
		. ($i + ($_GET["offset_num"])*$offset_amount) . ".<p>";
		echo "</table>";
	?>
	</body>
</html>
