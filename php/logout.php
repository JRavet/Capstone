<?php
	session_start();
	if(session_destroy())
	{
		if ($_GET["error"] == "")
		{
			header("Location: index.php");
		}
		else
		{
			header("Location: index.php?error=". $_GET["error"]); //include whether or not there was an error when redirecting to here
		}
	}
?>
