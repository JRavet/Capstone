<?php
	session_start();

	if(session_destroy())
	{
		header("Location: index.php?error=". $_GET["error"]); //include whether or not there was a login error when redirecting to here
	}
?>
