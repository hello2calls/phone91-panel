<?php
//Always place this code at the top of the Page
session_start();
if (isset($_SESSION['id'])) {
    // Redirection to login page twitter or facebook
    header("location: home.php");
}

if (array_key_exists("login", $_GET)) {
    $oauth_provider = $_GET['oauth_provider'];
    if ($oauth_provider == 'google') {
		header("Location: login-google.php");
	}
	else if ($oauth_provider == 'twitter') {
        header("Location: login-twitter.php");		
    } 
	else if($oauth_provider=='openID'){
		header("Location: login-openid.php");
	}
	else if($oauth_provider=='yahoo'){
		header("Location: login-yahoo.php");
	}
	else if($oauth_provider=='facebook'){
		header("Location: ../fblogin.php");
	}
	else if($oauth_provider=='linkedIn'){
		header("Location: linkedin.php");
	}
}
?>