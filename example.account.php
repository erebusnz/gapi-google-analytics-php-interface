<?php
require 'gapi.class.php';

$ga = gapiClientLogin::authenticate(ga_email,ga_password);
$ga = new gapi("XXXXXXXX@developer.gserviceaccount.com", "key.p12");

$ga->requestAccountData();

foreach($ga->getResults() as $result)
{
  echo $result . ' (' . $result->getProfileId() . ")<br />";
}
