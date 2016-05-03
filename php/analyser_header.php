<?php
	session_start();
	try
	{
		$conn = new PDO("mysql:host=localhost;dbname=Gw2Analyser","gw2analyser","themirrorimage");
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$userQuery="SELECT COUNT(*) FROM user WHERE name=\"" . $_SESSION["username"] . "\" AND password=password('". $_SESSION["password"] . "');";
		$user = $conn->query($userQuery);
		if ($user->fetchColumn() == 0)
		{ //check if the username/password combination exists in the database
			header("Location: logout.php?error=inv_cred"); //manually add a GET variable to tell the index-login page there was an error
		} //if it doesn't, return to the login page via logout (to remove session variables)
	}
	catch(PDOException $e)
	{
		header("Location: logout.php?error=db_err"); //manually add a GET variable to tell the index-login page there was an error
	}
	function createButton($pageName,$buttonText)
	{
		echo "<td><form action=\"" . $pageName . "\" method=\"GET\">";
		foreach(array_keys($_GET) as $key)
		{
			echo "<input type=\"hidden\" value=\"" . $_GET[$key] . "\" name=\"$key\">";
		}
		echo "<button class=\"btn btn-default btn-sm\" type=\"submit\">" . $buttonText . "</button>
		</form></td>";
		if (basename($_SERVER['PHP_SELF']) == $pageName)
		{
			$displayName = basename($_SERVER['PHP_SELF']);
			$displayName = str_replace("_"," ",$displayName);
			$displayName = str_replace(".php"," ",$displayName);
			$displayName = ucwords($displayName);
			echo "<center><h3>" . $displayName . "</h3></center>";
		}
	}
	function createDateTime($label,$name1,$name2)
	{
	 	echo "<tr><td>$label: </td><td><input type=\"datetime\" placeholder=\"YYYY-MM-DD hh:mm:ss\" name=\"$name1\" value=\"" . $_GET[$name1] . "\"/></td>
			<td>-</td><td><input type=\"datetime\" placeholder=\"YYYY-MM-DD hh:mm:ss\" name=\"$name2\" value=\"" . $_GET[$name2] . "\"/></td></tr>";
	}
	echo "<center><h1>GW2 Competitive Analytic Tool</h1></center>";
	echo "<table><tr>";
	createButton("match_details_analyser.php","Match Detail Analyser");
	createButton("activity_analyser.php","Activity Analyser");
	createButton("activity_map.php","Activity Map");
	createButton("guild_analyser.php","Guild Analyser");
	createButton("map_score_table.php","Map Score Table");
	createButton("map_score_graph.php","Map Score Graph");
	createButton("logout.php","Log Out");
	echo "</tr></table><br/>";
	echo "</div></nav>";
?>
<?php 
	function generate_option($value,$text,$select_name)
	{
		echo "<option value=\"" . $value . "\" ";
		if ($_GET[$select_name] == $value)
		{
			echo "selected=\"true\"";
		}
		echo ">" . $text . "</option>";
	}
	function check_inputs()
	{
		$pattern = '/;|"|\[|\]|\||>|<|\?|\/|\\\|{|}|\^|&|\$|@|#|%|\+|!/';
		foreach ($_GET as $param)
		{
			if (preg_match($pattern,$param) != 0)
			{
				die("<b>Invalid input</b>");
			}
		}
	}
	?>
