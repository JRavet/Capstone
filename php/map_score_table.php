<?php include 'analyser_header.php'; ?>
<html>
	<title> Map Score Analyser </title>
	<style>body{background:#FFF;}</style>
	<body>
	<?php
	echo "<form action=\"map_score_table.php\" method=\"GET\">
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
	<td><form action=\"map_score_table.php\">
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
			sum(red_ppt) as \"Red PPT\", s3.name as \"Green Server\",
			s2.name as \"Blue Server\", s1.name as \"Red Server\",
			sum(error_corrected) as \"Errors Corrected\",
			sum(greenKills)/sum(greenDeaths) as \"Green KD\",
			sum(blueKills)/sum(blueDeaths) as \"Blue KD\",
			sum(redKills)/sum(redDeaths) as \"Red KD\"
			FROM map_scores 
			INNER JOIN match_details ON map_scores.match_id = match_details.match_id
			INNER JOIN server_info s1 ON s1.srv_id = red_srv
			INNER JOIN server_info s2 ON s2.srv_id = blue_srv
			INNER JOIN server_info s3 ON s3.srv_id = green_srv
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
		echo "<table border=\"1\">";
		echo "<th>Row #</th><th>Match ID</th><th>Week #</th><th>Time Stamp</th><th>Server Name</th>
		<th>Kills</th><th>Deaths</th><th>KD Ratio</th><th>Score</th><th>PPT</th><th>Errors Corrected</th>";
		$i = 0;
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
			    //green
			    echo "<td bgcolor=\"#00cc00\">" . $row["Green Server"] . "</td>";
			    echo "<td bgcolor=\"#00cc00\">" . number_format($row["Green Kills"]) . "</td>";
			    echo "<td bgcolor=\"#00cc00\">" . number_format($row["Green Deaths"]) . "</td>";
			    echo "<td bgcolor=\"#00cc00\">" . number_format($row["Green KD"],3) . "</td>";
			    echo "<td bgcolor=\"#00cc00\">" . number_format($row["Green Score"]) . "</td>";
			    echo "<td bgcolor=\"#00cc00\">" . $row["Green PPT"] . "</td></tr>";
			   	//blue
			    echo "<tr>";
			    echo "<td></td><td></td><td></td><td></td>";
			    echo "<td bgcolor=\"#3399ff\">" . $row["Blue Server"] . "</td>";
			    echo "<td bgcolor=\"#3399ff\">" . number_format($row["Blue Kills"]) . "</td>";
			    echo "<td bgcolor=\"#3399ff\">" . number_format($row["Blue Deaths"]) . "</td>";
			    echo "<td bgcolor=\"#3399ff\">" . number_format($row["Blue KD"],3) . "</td>";
			    echo "<td bgcolor=\"#3399ff\">" . number_format($row["Blue Score"]) . "</td>";
			  	echo "<td bgcolor=\"#3399ff\">" . $row["Blue PPT"] . "</td></tr>";
			  	//red
			    echo "<tr>";
			    echo "<td></td><td></td><td></td><td></td>";
			    echo "<td bgcolor=\"#ff5050\">" . $row["Red Server"] . "</td>";
			    echo "<td bgcolor=\"#ff5050\">" . number_format($row["Red Kills"]) . "</td>";
			   	echo "<td bgcolor=\"#ff5050\">" . number_format($row["Red Deaths"]) . "</td>";
			    echo "<td bgcolor=\"#ff5050\">" . number_format($row["Red KD"],3) . "</td>";
			  	echo "<td bgcolor=\"#ff5050\">" . number_format($row["Red Score"]) . "</td>";
			  	echo "<td bgcolor=\"#ff5050\">" . $row["Red PPT"] . "</td></tr>";
			  	//totals
			    echo "<tr>";
			    echo "<td></td><td></td><td></td><td></td>";
			    echo "<td>Totals</td>";
			    echo "<td>" . number_format($row["Green Kills"] + $row["Blue Kills"] + $row["Red Kills"]) . "</td>";
			    echo "<td>" . number_format($row["Green Deaths"] + $row["Blue Deaths"] + $row["Red Deaths"]) . "</td>";
			   	echo "<td>" . number_format(($row["Green Kills"] + $row["Blue Kills"] + $row["Red Kills"])/($row["Green Deaths"] + $row["Blue Deaths"] + $row["Red Deaths"]),3) . "</td>";
			  	echo "<td>" . number_format($row["Green Score"] + $row["Blue Score"] + $row["Red Score"]) . "</td>";
			    echo "<td>0</td><td>" . $row["Errors Corrected"] . "</td></tr>";
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
		echo "</table><table border=\"1\">";
		echo "<th>Green Kills Total</th><th>Blue Kills Total</th><th>Red Kills Total</th><th>Total Kills</th>
		<th>Green Deaths Total</th><th>Blue Deaths Total</th><th>Red Deaths Total</th><th>Total Deaths</th>
		<th>Avg Green PPT</th><th>Avg Blue PPT</th><th>Avg Red PPT</th></tr>";
		echo "<tr>";
		echo "<td bgcolor=\"#00cc00\">" . $totalGrnKills . "</td>";
		echo "<td bgcolor=\"#3399ff\">" . $totalBluKills . "</td>";
		echo "<td bgcolor=\"#ff5050\">" . $totalRedKills . "</td>";
		echo "<td>" . ($totalGrnKills + $totalBluKills + $totalRedKills) . "</td>";
		echo "<td bgcolor=\"#00cc00\">" . $totalGrnDeaths . "</td>";
		echo "<td bgcolor=\"#3399ff\">" . $totalBluDeaths . "</td>";
		echo "<td bgcolor=\"#ff5050\">" . $totalRedDeaths . "</td>";
		echo "<td>" . ($totalGrnDeaths + $totalBluDeaths + $totalRedDeaths) . "</td>";
		echo "<td bgcolor=\"#00cc00\">" . (int)($totalGrnPPT/$count) . "</td>";
		echo "<td bgcolor=\"#3399ff\">" . (int)($totalBluPPT/$count) . "</td>";
		echo "<td bgcolor=\"#ff5050\">" . (int)($totalRedPPT/$count) . "</td>";		
		echo "</tr>";
		echo "</table>";
		echo "<p>Displaying results " . $_GET["offset_num"]*$offset_amount . "-" 
		. ($_GET["offset_num"]+1)*$offset_amount . " out of " 
		. ($i + ($_GET["offset_num"])*$offset_amount) . ".</p>";
	?>
	</body>
</html>
