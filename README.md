# GAPI now has OAuth2 support #

**GAPI is now at version 2.0** - This version has full OAuth2 and V3 authentication support.

Development is complete on the Google Analytics filter control. You can now filter your results using a simple GAPI filter string, for example:

```
$filter = 'country == United States && browser == Firefox || browser == Chrome';
```

You can create simple query strings that represent the logic Google Analytics requires, but it is abstracted enough to be more readable and easier to work with.

Download the latest [gapi.class.php](https://github.com/erebusnz/gapi-google-analytics-php-interface) and try out the filter control with the example.filter.php. Read more about the [GAPI Filter Control](https://github.com/erebusnz/gapi-google-analytics-php-interface/blob/wiki/UsingFilterControl.md).

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
$ga = new gapi('XXXX@developer.gserviceaccount.com','oauthkeyfile.p12');

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

For example, if you request the metric 'uniquePageviews' and the dimension 'pagePath' you can do the following:

```
foreach($ga->getResults() as $result)
{
  echo $result->getUniquePageviews();
  echo $result->getPagePath();
}
```

## Instructions for setting up a Google service account for use with GAPI

GAPI (because now Google Analytics API only supports OAuth2) will require you to create a 'service account' and then download a .P12 file to upload to your application.

  1. Create a [Google Developers project](https://console.developers.google.com/project)
  2. Create service account under this project, [see instructions](https://developers.google.com/identity/protocols/OAuth2ServiceAccount#creatinganaccount)
  3. Download the .p12 file for this service account, upload to the same folder as **gapi.class.php**
  4. Enable 'analytics API' in the [Google Developers console]((https://console.developers.google.com/project))
  5. In Google Analytics *Administration > User Management*, give the service account 'Read and Analyse' permissions on the analytics accounts you want to access
