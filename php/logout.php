<?php
	if (session_status() == PHP_SESSION_NONE)
	{
		header("Location: index.php?error=session_closed");
	}
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
	else
	{
		header("Location: index.php");
	}
?>

