<?php include 'analyser_header.php'; ?>
<html>
	<title> Map Score Analyser </title>
	<body>
	<?php
	echo "<form action=\"map_score_analyser.php\" method=\"GET\">
	<table>";
	echo "<tr><td>Sort by:</td><td><select name=\"sort_by\">";
		generate_option("week_num,timeStamp,map_scores.match_id","Week Number, Time Stamp, Match ID","sort_by");
		generate_option("week_num,map_scores.match_id,timeStamp","Week Number, Match ID, Time Stamp","sort_by");
		generate_option("timeStamp","Time Stamp","sort_by");
		generate_option("map_scores.match_id","Match ID","sort_by");
		generate_option("week_num","Week Number","sort_by");
	echo "</select></tr>
		<tr><td>Match ID: </td><td><input type=\"text\" name=\"match_id\" value=\"" . $_GET["match_id"] . "\"/></td></tr> 
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
		echo "<table border=\"1\">";
		echo "<th>Row #</th><th>Match ID</th><th>Week Number</th><th>Time Stamp</th><th>|</th><th>Green Kills</th><th>Blue Kills</th>
		<th>Red Kills</th><th>Total Kills</th><th>|</th><th>Green Deaths</th>
		<th>Blue Deaths</th><th>Red Deaths</th><th>Total Deaths</th><th>|</th>
		<th>Green Score</th><th>Blue Score</th><th>Red Score</th><th>Total Score</th><th>|</th>
		<th>Green PPT</th><th>Blue PPT</th><th>Red PPT</th><th>|</th><th>Green Server</th><th>Blue Server</th><th>Red Server</th><th>Errors Corrected</th>";
		$i = 0;
		$resultSet = $conn->query($scoreQuery);
		$totalGrnKills=0;
		$totalBluKills=0;
		$totalRedKills=0;
		//
		$totalGrnDeaths=0;
		$totalBluDeaths=0;
		$totalRedDeaths=0;
		//
		$totalGrnPPT=0;
		$totalBluPPT=0;
		$totalRedPPT=0;
		//
		$count=0;
		foreach ($resultSet as $row)
		{
			$i++;
			if ($i < $offset_amount + 1)
			{
				$count++;
				$totalGrnKills += $row["Green Kills"];
				$totalBluKills += $row["Blue Kills"];
				$totalRedKills += $row["Red Kills"];
				//
				$totalGrnDeaths += $row["Green Deaths"];
				$totalBluDeaths += $row["Blue Deaths"];
				$totalRedDeaths += $row["Red Deaths"];
	
				//
				$totalGrnPPT += $row["Green PPT"];
				$totalBluPPT += $row["Blue PPT"];
				$totalRedPPT += $row["Red PPT"];
				//
			    echo "<tr>";
			    echo "<td>" . $i . "</td>";
			    echo "<td>" . $row["Match ID"] . "</td>";
			    echo "<td>" . $row["Week Number"] . "</td>";
			    echo "<td>" . $row["Time Stamp"] . "</td>";
			    echo "<td>|</td>";
			    echo "<td bgcolor=\"#00cc00\">" . $row["Green Kills"] . "</td>";
			    echo "<td bgcolor=\"#3399ff\">" . $row["Blue Kills"] . "</td>";
			    echo "<td bgcolor=\"#ff5050\">" . $row["Red Kills"] . "</td>";
			  	echo "<td>" . ($row["Green Kills"] + $row["Blue Kills"] + $row["Red Kills"]) . "</td>";
			  	echo "<td>|</td>";
			    echo "<td bgcolor=\"#00cc00\">" . $row["Green Deaths"] . "</td>";
			    echo "<td bgcolor=\"#3399ff\">" . $row["Blue Deaths"] . "</td>";
			    echo "<td bgcolor=\"#ff5050\">" . $row["Red Deaths"] . "</td>";
			  	echo "<td>" . ($row["Green Deaths"] + $row["Blue Deaths"] + $row["Red Deaths"]) . "</td>";
			  	echo "<td>|</td>";
			    echo "<td bgcolor=\"#00cc00\">" . $row["Green Score"] . "</td>";
			    echo "<td bgcolor=\"#3399ff\">" . $row["Blue Score"] . "</td>";
			    echo "<td bgcolor=\"#ff5050\">" . $row["Red Score"] . "</td>";
			  	echo "<td>" . ($row["Green Score"] + $row["Blue Score"] + $row["Red Score"]) . "</td>";
			  	echo "<td>|</td>";
			    echo "<td bgcolor=\"#00cc00\">" . $row["Green PPT"] . "</td>";
			    echo "<td bgcolor=\"#3399ff\">" . $row["Blue PPT"] . "</td>";
			    echo "<td bgcolor=\"#ff5050\">" . $row["Red PPT"] . "</td>";
			    echo "<td>|</td>";
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
		echo "<tr><th></th><th></th><th></th><th></th><th>|</th><th>Green Kills Total</th><th>Blue Kills Total</th><th>Red Kills Total</th><th>Total Kills</th>
		<th>|</th><th>Green Deaths Total</th><th>Blue Deaths Total</th><th>Red Deaths Total</th><th>Total Deaths</th>
		<th>|</th><th></th><th></th><th></th><th></th><th>|</th><th>Avg Green PPT</th><th>Avg Blue PPT</th><th>Avg Red PPT</th><th>|</th></tr>";
		echo "<tr><td></td><td></td><td></td><td></td><td>|</td>";
		echo "<td bgcolor=\"#00cc00\">" . $totalGrnKills . "</td>";
		echo "<td bgcolor=\"#3399ff\">" . $totalBluKills . "</td>";
		echo "<td bgcolor=\"#ff5050\">" . $totalRedKills . "</td>";
		echo "<td>" . ($totalGrnKills + $totalBluKills + $totalRedKills) . "</td>";
		echo "<td>|</td>";
		echo "<td bgcolor=\"#00cc00\">" . $totalGrnDeaths . "</td>";
		echo "<td bgcolor=\"#3399ff\">" . $totalBluDeaths . "</td>";
		echo "<td bgcolor=\"#ff5050\">" . $totalRedDeaths . "</td>";
		echo "<td>" . ($totalGrnDeaths + $totalBluDeaths + $totalRedDeaths) . "</td>";
		echo "<td>|</td><td></td><td></td><td></td><td></td><td>|</td>";
		echo "<td bgcolor=\"#00cc00\">" . (int)($totalGrnPPT/$count) . "</td>";
		echo "<td bgcolor=\"#3399ff\">" . (int)($totalBluPPT/$count) . "</td>";
		echo "<td bgcolor=\"#ff5050\">" . (int)($totalRedPPT/$count) . "</td>";
		echo "<td>|</td>";
		
		echo " </tr>";
		echo "Displaying results " . $_GET["offset_num"]*$offset_amount . "-" 
		. ($_GET["offset_num"]+1)*$offset_amount . " out of " 
		. ($i + ($_GET["offset_num"])*$offset_amount) . ".<p>";
		echo "</table>";
		echo "<p>Displaying results " . $_GET["offset_num"]*$offset_amount . "-" 
		. ($_GET["offset_num"]+1)*$offset_amount . " out of " 
		. ($i + ($_GET["offset_num"])*$offset_amount) . ".</p>";
	?>
	</body>
</html>
