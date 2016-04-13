<?php
	session_start();
	try
	{
		$conn = new PDO("mysql:host=localhost;dbname=Gw2Analyser", $_SESSION["username"], $_SESSION["password"]);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch(PDOException $e)
	{
		header("Location: logout.php?error=inv_cred"); //manually add a GET variable to tell the index-login page there was an error
	}
	function createButton($pageName,$buttonText)
	{
		echo "<td><form action=\"" . $pageName . "\">
			<input type=\"submit\" value=\"" . $buttonText ."\">
		</form></td>";
	}
	
	echo "<table><tr>";
	createButton("activity_analyser.php","Activity Analyser");
	createButton("guild_analyser.php","Guild Analyser");
	createButton("map_score_table.php","Map Score Table");
	createButton("map_score_graph.php","Map Score Graph");
	createButton("logout.php","Log Out");
	echo "</tr></table><br/>";
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
		$pattern = '|()[]{}/\\\'\"+_;*&^%$#@!?<>,';
		foreach ($_GET as $param)
		{
			if (strpos($pattern,$param) !== false)
			{
				die("Invalid input");
			}
		}
	}
	?>
