<?php
define('ga_email','youremail@email.com');
define('ga_password','your password');

require 'gapi.class.php';

$ga_auth_token = $_SESSION['ga_auth_token'] ?
  $_SESSION['ga_auth_token'] : null;

$ga = gapiClientLogin->authenticate(ga_email,ga_password,$ga_auth_token);
$_SESSION['ga_auth_token'] = $ga->getAuthToken();

echo 'Token: ' . $_SESSION['ga_auth_token'];
?>
