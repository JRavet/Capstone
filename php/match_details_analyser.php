<?php include 'analyser_header.php'; ?>
<html>
	<title> Match Details Analyser </title>
	<style>body{background:#FFF;}</style>
	<body>
	<?php
	echo "<form action=\"match_details_analyser.php\" method=\"GET\">
	<table>";
	echo "<tr><td>Region / Match Number:</td><td> <select name=\"region\">";
		generate_option("","","region");
		generate_option("1","NA","region");
		generate_option("2","EU","region");
		echo "</select><input type=\"number\" min=\"1\" max=\"9\" name=\"match_num\" value=\"" . $_GET["match_num"] . "\"/></td></tr> 
		<tr><td>Week number: </td><td><input type=\"number\" min=\"0\" max=\"52\" name=\"week_num\" value=\"" . $_GET["week_num"] . "\"/></td></tr>";
	echo "</table>
	<table>
	<tr>
	<td><input type=\"submit\" value=\"Submit Query\"/></td><td style=\"width:175px\"></td>
	</form></td>
	<td><form action=\"match_details_analyser.php\" method=\"GET\">
		<input type=\"submit\" value=\"Reset fields\"/>
	</form></td>
	</tr>
	</table>";
	?>
	<br/>
	<?php
		$matchQuery = "SELECT match_id as \"Match ID\", week_num as \"Week Number\",
			start_time as \"Start Time\", end_time as \"End Time\", s1.name as \"Red Server\", s1_1.name as \"Red Server2\",
			s2.name as \"Blue Server\", s2_1.name as \"Blue Server2\", s3.name as \"Green Server\", s3_1.name as \"Green Server2\",
			grn_srv_population as \"Green Population\", blu_srv_population as \"Blue Population\", red_srv_population as \"Red Population\",
			grn_srv2_pop as \"Green Population2\", blu_srv2_pop as \"Blue Population2\", red_srv2_pop as \"Red Population2\"
			FROM match_details
			LEFT JOIN server_info s1_1 on s1_1.srv_id = red_srv2
			LEFT JOIN server_info s2_1 on s2_1.srv_id = blue_srv2
			LEFT JOIN server_info s3_1 on s3_1.srv_id = green_srv2
			INNER JOIN server_info s1 ON s1.srv_id = red_srv
			INNER JOIN server_info s2 ON s2.srv_id = blue_srv
			INNER JOIN server_info s3 ON s3.srv_id = green_srv
			WHERE match_id = match_id "; //dummy where clause
		check_inputs();
		if ($_GET["region"] != "")
		{
			$matchQuery .= "and match_id LIKE \"" . $_GET["region"] . "-%\" ";
		}
		if ($_GET["match_num"] != "")
		{
			$matchQuery .= "and match_id LIKE \"%-" . $_GET["match_num"] . "\" ";
		}
		if ($_GET["week_num"] != "")
		{
			$matchQuery .= "and week_num = \"" . $_GET["week_num"] . "\" ";
		}
		$matchQuery .= " ORDER BY week_num DESC,match_id ASC LIMIT 68;";
		//
		echo "<table border=\"1\">";
		echo "<th>Match ID</th><th>Week Number</th>
		<th>Green Server<p>Population</th><th>Blue Server<p>Population</th><th>Red Server<p>Population</th>
		<th>Start Time</th><th>End Time</th>";
		$resultSet = $conn->query($matchQuery);
		//
		foreach ($resultSet as $row)
		{
			echo "<tr>";
			echo "<td>" . $row["Match ID"] . "</td>";
			echo "<td>" . $row["Week Number"] . "</td>";
			echo "<td bgcolor=\"#00cc00\"><center>" . $row["Green Server"] . " (" . $row["Green Population"] . ")";
			if ($row["Green Server2"] != "")
			{
				echo "<br>" . $row["Green Server2"] . " (" . $row["Green Population2"] . ")";
			}
			echo "</center></td>";
			echo "<td bgcolor=\"#3399ff\"><center>" . $row["Blue Server"] . " (" . $row["Blue Population"] . ")";
			if ($row["Blue Server2"] != "")
			{
				echo "<br>" . $row["Blue Server2"] . " (" . $row["Blue Population2"] . ")";
			}
			echo "</center></td>";
			echo "<td bgcolor=\"#ff5050\"><center>" . $row["Red Server"] . " (" . $row["Red Population"] . ")";
			if ($row["Red Server2"] != "")
			{
				echo "<br>" . $row["Red Server2"] . " (" . $row["Red Population2"] . ")";
			}
			echo "</center></td>";
			echo "<td>" . $row["Start Time"] . "</td>";
			echo "<td>" . $row["End Time"] . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	?>
	</body>
</html>
