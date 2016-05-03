<?php
	session_start();
	$_SESSION["username"] = $_POST["username"];
	$_SESSION["password"] = $_POST["password"];
	header("Location: match_details_analyser.php");
?>



