# GAPI now has Google Analytics filter support #

**GAPI is now at version 1.3** - This version contains fixes for the handling of very large metric values represented in scientific notation. Thanks to austinrehab for raising this issue.

Development is complete on the Google Analytics filter control. You can now filter your results using a simple GAPI filter string, for example:

```
$filter = 'country == United States && browser == Firefox || browser == Chrome';
```

You can create simple query strings that represent the logic Google Analytics requires, but it is abstracted enough to be more readable and easier to work with.

Download the latest [gapi.class.php](http://gapi-google-analytics-php-interface.googlecode.com/files/gapi-1.3.zip) and try out the filter control with the example.filter.php. Read more about the [GAPI Filter Control](http://code.google.com/p/gapi-google-analytics-php-interface/wiki/UsingFilterControl).

## Features: ##

  * Supports CURL and fopen HTTP access methods, with autodetection
  * PHP arrays for Google Analytics metrics and dimensions
  * Account data object mapping - get methods for parameters
  * Report data object mapping - get methods for metrics and parameters
  * Easy filtering, use a GAPI query language for Google Analytics filters
  * Full PHP5 Object Oriented code, ready for use in your PHP application

GAPI (said 'g,a,p,i') is the Google Analytics PHP5 Interface.

Need google analytics interface in your OO PHP5 project?

You might be running symfony, zend framework, cakePHP and need a good object-oriented interface to get those stats. This class gives a good clean class based interface.

## Use is as simple as: ##

```
$ga = new gapi('email@yourdomain.com','password');

$ga->requestReportData(145141242,array('browser','browserVersion'),array('pageviews','visits'));

foreach($ga->getResults() as $result)
{
  echo '<strong>'.$result.'</strong><br />';
  echo 'Pageviews: ' . $result->getPageviews() . ' ';
  echo 'Visits: ' . $result->getVisits() . '<br />';
}

echo '<p>Total pageviews: ' . $ga->getPageviews() . ' total visits: ' . $ga->getVisits() . '</p>';
```

This project was inspired by the use of Doctrine and Propel ORM interfaces for PHP. Dealing with complex data should be easy!

## Access metrics and dimensions using magic get methods ##

With GAPI, when data is returned from Google it is automatically converted into a native PHP object, with an interface to allow the 'get' the value of any dimesion or metric.

For example, if you request the metric 'uniquePageviews' and the dimesion 'pagePath' you can do the following:

```
foreach($ga->getResults() as $result)
{
  echo $result->getUniquePageviews();
  echo $result->getPagePath();
}
```