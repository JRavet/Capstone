<?php
	session_start();
	//$_SESSION["username"] = $_POST["username"];
	//$_SESSION["password"] = $_POST["password"];
	$_SESSION["username"] = 'gw2analyser';
	$_SESSION["password"] = 'themirrorimage';
	header("Location: " . $_POST["analyser_type"]);
?>

