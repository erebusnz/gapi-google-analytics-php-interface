<?php
define('ga_email','youremail@email.com');
define('ga_password','your password');

require 'gapi.class.php';

$ga = gapiClientLogin::authenticate(ga_email,ga_password);

$ga->requestAccountData();

foreach($ga->getResults() as $result)
{
  echo $result . ' (' . $result->getProfileId() . ")<br />";
}
