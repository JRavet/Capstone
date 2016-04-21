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
			start_time as \"Start Time\", end_time as \"End Time\", s1.name as \"Red Server\",
			s2.name as \"Blue Server\", s3.name as \"Green Server\", grn_srv_population as \"Green Population\",
			blu_srv_population as \"Blue Population\", red_srv_population as \"Red Population\"
			FROM match_details
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
		<th>Start Time</th><th>End Time</th>
		<th>Green Server<p>Population</th><th>Blue Server<p>Population</th><th>Red Server<p>Population</th>";
		$resultSet = $conn->query($matchQuery);
		//
		foreach ($resultSet as $row)
		{
			echo "<tr>";
			echo "<td>" . $row["Match ID"] . "</td>";
			echo "<td>" . $row["Week Number"] . "</td>";
			echo "<td bgcolor=\"#00cc00\"><center>" . $row["Green Server"] . "<p>" . $row["Green Population"] . "</center></td>";
			echo "<td bgcolor=\"#3399ff\"><center>" . $row["Blue Server"] . "<p>" . $row["Blue Population"] . "</center></td>";
			echo "<td bgcolor=\"#ff5050\"><center>" . $row["Red Server"] . "<p>" . $row["Red Population"] . "</center></td>";
			echo "<td>" . $row["Start Time"] . "</td>";
			echo "<td>" . $row["End Time"] . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	?>
	</body>
</html>
