<?php
// $Id: gapi.class.php,v 1.4 2009/06/19 01:48:19 jkitching Exp $

/**
 * GAPI - Google Analytics PHP Interface
 * 
 * http://code.google.com/p/gapi-google-analytics-php-interface/
 * 
 * @copyright Stig Manning 2009
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author Stig Manning <stig@sdm.co.nz>
 * @author Joel Kitching <jkitching@mailbolt.com>
 * @version 1.3
 * 
 */

class gapi {
  const account_data_url = 'https://www.google.com/analytics/feeds/accounts/default';
  const report_data_url = 'https://www.google.com/analytics/feeds/data';
  const interface_name = 'GAPI-1.3';
  const dev_mode = false;

  private $auth_method = null;
  private $account_entries = array();
  private $account_root_parameters = array();
  private $report_aggregate_metrics = array();
  private $report_root_parameters = array();
  private $results = array();

  /**
   * Constructor function for all new gapi instances
   * 
   * Pass the constructor a concrete gapiAuthMethod instance
   *
   * @param gapiAuthMethod $auth_method
   * @return gapi
   */
  public function __construct($auth_method) {
    $this->auth_method = $auth_method;
  }

  /**
   * Return the auth token string retrieved by Google
   *
   * @return String
   */
  public function getToken() {
    return $this->auth_method->getToken();
  }

  /**
   * Return the auth token information from the Google service
   *
   * @return Array
   */
  public function getTokenInfo() {
    return $this->auth_method->getTokenInfo();
  }

  /**
   * Revoke the current auth token, rendering it invalid for future requests
   *
   * @return Boolean
   */
  public function revokeToken() {
    return $this->auth_method->revokeToken();
  }

  /**
   * Request account data from Google Analytics
   *
   * @param Int $start_index OPTIONAL: Start index of results
   * @param Int $max_results OPTIONAL: Max results returned
   */
  public function requestAccountData($start_index=1, $max_results=20) {
    $post_variables = array(
      'start-index' => $start_index,
      'max-results' => $max_results,
      );
    $url = new gapiUrl(gapi::account_data_url);
    $response = $url->post($post_variables, null, $this->auth_method->generateAuthHeader());

    if (substr($response['code'], 0, 1) == '2') {
      return $this->accountObjectMapper($response['body']);
    } else {
      throw new Exception('GAPI: Failed to request account data. Error: "' . strip_tags($response['body']) . '"');
    }
  }

  /**
   * Request report data from Google Analytics
   *
   * $report_id is the Google report ID for the selected account
   * 
   * $parameters should be in key => value format
   * 
   * @param String $report_id
   * @param Array $dimensions Google Analytics dimensions e.g. array('browser')
   * @param Array $metrics Google Analytics metrics e.g. array('pageviews')
   * @param Array $sort_metric OPTIONAL: Dimension or dimensions to sort by e.g.('-visits')
   * @param String $filter OPTIONAL: Filter logic for filtering results
   * @param String $start_date OPTIONAL: Start of reporting period
   * @param String $end_date OPTIONAL: End of reporting period
   * @param Int $start_index OPTIONAL: Start index of results
   * @param Int $max_results OPTIONAL: Max results returned
   */
  public function requestReportData($report_id, $dimensions=null, $metrics, $sort_metric=null, $filter=null, $start_date=null, $end_date=null, $start_index=1, $max_results=30) {
    $parameters = array('ids'=>'ga:' . $report_id);

    if (is_array($dimensions)) {
      $dimensions_string = '';
      foreach ($dimensions as $dimesion) {
        $dimensions_string .= ',ga:' . $dimesion;
      }
      $parameters['dimensions'] = substr($dimensions_string, 1);
    } elseif ($dimensions !== null) {
      $parameters['dimensions'] = 'ga:'.$dimensions;
    }

    if (is_array($metrics)) {
      $metrics_string = '';
      foreach ($metrics as $metric) {
        $metrics_string .= ',ga:' . $metric;
      }
      $parameters['metrics'] = substr($metrics_string, 1);
    } else {
      $parameters['metrics'] = 'ga:'.$metrics;
    }

    if ($sort_metric==null&&isset($parameters['metrics'])) {
      $parameters['sort'] = $parameters['metrics'];
    } elseif (is_array($sort_metric)) {
      $sort_metric_string = '';

      foreach ($sort_metric as $sort_metric_value) {
        //Reverse sort - Thanks Nick Sullivan
        if (substr($sort_metric_value, 0, 1) == "-") {
          $sort_metric_string .= ',-ga:' . substr($sort_metric_value, 1); // Descending
        }
        else {
          $sort_metric_string .= ',ga:' . $sort_metric_value; // Ascending
        }
      }

      $parameters['sort'] = substr($sort_metric_string, 1);
    } else {
      if (substr($sort_metric, 0, 1) == "-") {
        $parameters['sort'] = '-ga:' . substr($sort_metric, 1);
      } else {
        $parameters['sort'] = 'ga:' . $sort_metric;
      }
    }

    if ($filter!=null) {
      $filter = $this->processFilter($filter);
      if ($filter!==false) {
        $parameters['filters'] = $filter;
      }
    }

    if ($start_date==null) {
      $start_date=date('Y-m-d', strtotime('1 month ago'));
    }

    $parameters['start-date'] = $start_date;

    if ($end_date==null) {
      $end_date=date('Y-m-d');
    }

    $parameters['end-date'] = $end_date;


    $parameters['start-index'] = $start_index;
    $parameters['max-results'] = $max_results;

    $parameters['prettyprint'] = gapi::dev_mode ? 'true' : 'false';

    $url = new gapiUrl(gapi::report_data_url);
    $response = $url->get($parameters, $this->auth_method->generateAuthHeader());

    //HTTP 2xx
    if (substr($response['code'], 0, 1) == '2') {
      return $this->reportObjectMapper($response['body']);
    } else {
      throw new Exception('GAPI: Failed to request report data. Error: "' . strip_tags($response['body']) . '"');
    }
  }

  /**
   * Process filter string, clean parameters and convert to Google Analytics
   * compatible format
   * 
   * @param String $filter
   * @return String Compatible filter string
   */
  protected function processFilter($filter) {
    $valid_operators = '(!~|=~|==|!=|>|<|>=|<=|=@|!@)';

    $filter = preg_replace('/\s\s+/', ' ', trim($filter)); //Clean duplicate whitespace
    $filter = str_replace(array(',', ';'), array('\,', '\;'), $filter); //Escape Google Analytics reserved characters
    $filter = preg_replace('/(&&\s*|\|\|\s*|^)([a-z]+)(\s*' . $valid_operators . ')/i','$1ga:$2$3',$filter); //Prefix ga: to metrics and dimensions
    $filter = preg_replace('/[\'\"]/i', '', $filter); //Clear invalid quote characters
    $filter = preg_replace(array('/\s*&&\s*/','/\s*\|\|\s*/','/\s*' . $valid_operators . '\s*/'), array(';', ',', '$1'), $filter); //Clean up operators

    if (strlen($filter) > 0) {
      return urlencode($filter);
    }
    else {
      return false;
    }
  }

  /**
   * Report Account Mapper to convert the XML to array of useful PHP objects
   *
   * @param String $xml_string
   * @return Array of gapiAccountEntry objects
   */
  protected function accountObjectMapper($xml_string) {
    $xml = simplexml_load_string($xml_string);

    $this->results = null;

    $results = array();
    $account_root_parameters = array();

    //Load root parameters

    $account_root_parameters['updated'] = strval($xml->updated);
    $account_root_parameters['generator'] = strval($xml->generator);
    $account_root_parameters['generatorVersion'] = strval($xml->generator->attributes());

    $open_search_results = $xml->children('http://a9.com/-/spec/opensearchrss/1.0/');

    foreach ($open_search_results as $key => $open_search_result) {
      $report_root_parameters[$key] = intval($open_search_result);
    }

    $account_root_parameters['startDate'] = strval($google_results->startDate);
    $account_root_parameters['endDate'] = strval($google_results->endDate);

    //Load result entries

    foreach ($xml->entry as $entry) {
      $properties = array();
      foreach ($entry->children('http://schemas.google.com/analytics/2009')->property as $property) {
        $properties[str_replace('ga:','',$property->attributes()->name)] = strval($property->attributes()->value);
      }

      $properties['title'] = strval($entry->title);
      $properties['updated'] = strval($entry->updated);

      $results[] = new gapiAccountEntry($properties);
    }

    $this->account_root_parameters = $account_root_parameters;
    $this->results = $results;
    $this->account_entries = $results;

    return $results;
  }

  /**
   * Report Object Mapper to convert the XML to array of useful PHP objects
   *
   * @param String $xml_string
   * @return Array of gapiReportEntry objects
   */
  protected function reportObjectMapper($xml_string) {
    $xml = simplexml_load_string($xml_string);

    $this->results = null;
    $results = array();

    $report_root_parameters = array();
    $report_aggregate_metrics = array();

    //Load root parameters

    $report_root_parameters['updated'] = strval($xml->updated);
    $report_root_parameters['generator'] = strval($xml->generator);
    $report_root_parameters['generatorVersion'] = strval($xml->generator->attributes());

    $open_search_results = $xml->children('http://a9.com/-/spec/opensearchrss/1.0/');

    foreach ($open_search_results as $key => $open_search_result) {
      $report_root_parameters[$key] = intval($open_search_result);
    }

    $google_results = $xml->children('http://schemas.google.com/analytics/2009');

    foreach ($google_results->dataSource->property as $property_attributes) {
      $report_root_parameters[str_replace('ga:', '', $property_attributes->attributes()->name)] = strval($property_attributes->attributes()->value);
    }

    $report_root_parameters['startDate'] = strval($google_results->startDate);
    $report_root_parameters['endDate'] = strval($google_results->endDate);

    //Load result aggregate metrics

    foreach ($google_results->aggregates->metric as $aggregate_metric) {
      $metric_value = strval($aggregate_metric->attributes()->value);

      //Check for float, or value with scientific notation
      if (preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/', $metric_value)) {
        $report_aggregate_metrics[str_replace('ga:', '', $aggregate_metric->attributes()->name)] = floatval($metric_value);
      } else {
        $report_aggregate_metrics[str_replace('ga:', '', $aggregate_metric->attributes()->name)] = intval($metric_value);
      }
    }

    //Load result entries

    foreach ($xml->entry as $entry) {
      $metrics = array();
      foreach ($entry->children('http://schemas.google.com/analytics/2009')->metric as $metric) {
        $metric_value = strval($metric->attributes()->value);

        //Check for float, or value with scientific notation
        if(preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/',$metric_value)) {
          $metrics[str_replace('ga:', '', $metric->attributes()->name)] = floatval($metric_value);
        } else {
          $metrics[str_replace('ga:', '', $metric->attributes()->name)] = intval($metric_value);
        }
      }

      $dimensions = array();
      foreach ($entry->children('http://schemas.google.com/analytics/2009')->dimension as $dimension) {
        $dimensions[str_replace('ga:', '', $dimension->attributes()->name)] = strval($dimension->attributes()->value);
      }

      $results[] = new gapiReportEntry($metrics, $dimensions);
    }

    $this->report_root_parameters = $report_root_parameters;
    $this->report_aggregate_metrics = $report_aggregate_metrics;
    $this->results = $results;

    return $results;
  }

  /**
   * Get Results
   *
   * @return Array
   */
  public function getResults() {
    return is_array($this->results) ? $this->results : false;
  }


  /**
   * Get an array of the metrics and the matchning
   * aggregate values for the current result
   *
   * @return Array
   */
  public function getMetrics() {
    return $this->report_aggregate_metrics;
  }

  /**
   * Call method to find a matching root parameter or 
   * aggregate metric to return
   *
   * @param $name String name of function called
   * @return String
   * @throws Exception if not a valid parameter or aggregate 
   * metric, or not a 'get' function
   */
  public function __call($name, $parameters) {
    if (!preg_match('/^get/', $name)) {
      throw new Exception('No such function "' . $name . '"');
    }

    $name = preg_replace('/^get/', '', $name);

    $parameter_key = array_key_exists_nc($name, $this->report_root_parameters);

    if ($parameter_key) {
      return $this->report_root_parameters[$parameter_key];
    }

    $aggregate_metric_key = array_key_exists_nc($name, $this->report_aggregate_metrics);

    if ($aggregate_metric_key) {
      return $this->report_aggregate_metrics[$aggregate_metric_key];
    }

    throw new Exception('No valid root parameter or aggregate metric called "' . $name . '"');
  }
}

/**
 * Class gapiAccountEntry
 * 
 * Storage for individual gapi account entries
 *
 */
class gapiAccountEntry {
  private $properties = array();

  /**
   * Constructor function for all new gapiAccountEntry instances
   * 
   * @param Array $properties
   * @return gapiAccountEntry
   */
  public function __construct($properties) {
    $this->properties = $properties;
  }

  /**
   * toString function to return the name of the account
   *
   * @return String
   */
  public function __toString() {
    return isset($this->properties['title']) ?
      $this->properties['title']: false;
  }

  /**
   * Get an associative array of the properties
   * and the matching values for the current result
   *
   * @return Array
   */
  public function getProperties() {
    return $this->properties;
  }

  /**
   * Call method to find a matching parameter to return
   *
   * @param $name String name of function called
   * @return String
   * @throws Exception if not a valid parameter, or not a 'get' function
   */
  public function __call($name, $parameters) {
    if (!preg_match('/^get/', $name)) {
      throw new Exception('No such function "' . $name . '"');
    }

    $name = preg_replace('/^get/', '', $name);

    $property_key = array_key_exists_nc($name, $this->properties);

    if ($property_key) {
      return $this->properties[$property_key];
    }

    throw new Exception('No valid property called "' . $name . '"');
  }
}

/**
 * Class gapiReportEntry
 * 
 * Storage for individual gapi report entries
 *
 */
class gapiReportEntry {
  private $metrics = array();
  private $dimensions = array();

  /**
   * Constructor function for all new gapiReportEntry instances
   * 
   * @param Array $metrics
   * @param Array $dimensions
   * @return gapiReportEntry
   */
  public function __construct($metrics, $dimensions) {
    $this->metrics = $metrics;
    $this->dimensions = $dimensions;
  }

  /**
   * toString function to return the name of the result
   * this is a concatented string of the dimensions chosen
   * 
   * For example:
   * 'Firefox 3.0.10' from browser and browserVersion
   *
   * @return String
   */
  public function __toString() {
    return is_array($this->dimensions) ? 
      implode(' ', $this->dimensions) : '';
  }

  /**
   * Get an associative array of the dimensions
   * and the matching values for the current result
   *
   * @return Array
   */
  public function getDimensions() {
    return $this->dimensions;
  }

  /**
   * Get an array of the metrics and the matchning
   * values for the current result
   *
   * @return Array
   */
  public function getMetrics() {
    return $this->metrics;
  }

  /**
   * Call method to find a matching metric or dimension to return
   *
   * @param String $name name of function called
   * @param Array $parameters
   * @return String
   * @throws Exception if not a valid metric or dimensions, or not a 'get' function
   */
  public function __call($name, $parameters) {
    if (!preg_match('/^get/', $name)) {
      throw new Exception('No such function "' . $name . '"');
    }

    $name = preg_replace('/^get/', '', $name);

    $metric_key = array_key_exists_nc($name, $this->metrics);

    if ($metric_key) {
      return $this->metrics[$metric_key];
    }

    $dimension_key = array_key_exists_nc($name, $this->dimensions);

    if ($dimension_key) {
      return $this->dimensions[$dimension_key];
    }

    throw new Exception('No valid metric or dimesion called "' . $name . '"');
  }
}

/**
 * Class gapiAuthMethod
 * 
 * Abstract class representing an authorization method
 *
 */
abstract class gapiAuthMethod {
  protected $auth_token = null;

  /**
   * Constructs a new gapiAuthMethod class given an existing token
   *
   * @param String $auth_token
   * @return gapiAuthMethod
   */
  public function __construct($auth_token=null) {
    $this->auth_token = $auth_token;
  }

  /**
   * Return the auth token string retrieved from Google
   *
   * @return String
   */
  public function getToken() {
    return $this->auth_token;
  }

  /**
   * Abstract method that returns the authorization method name
   *
   * @return String
   */
  protected static abstract function getMethodName();

  /**
   * Abstract method that returns the identifier of the token in the auth header
   *
   * @return String
   */
  protected static abstract function getTokenName();

  /**
   * gapi factory: return an instance of gapi seeded with the auth token method
   * (for use when one already has an authorization token string)
   *
   * @param String $auth_token
   * @return gapi
   */
  public static function withToken($auth_token) {
    $class_name = get_called_class();
    return new gapi(new $class_name($auth_token));
  }

  /**
   * Generate authorization token header for all requests
   *
   * @param String $token
   * @return Array
   */
  public function generateAuthHeader($token=null) {
    if ($token == null)
      $token = $this->auth_token;
    return array('Authorization' => $this->getMethodName() . ' ' . $this->getTokenName() . '=' . $token);
  }

  /**
   * Parse the body of a returned key=value page
   *
   * @param String $content
   * @return Array
   */
  protected function parseBody($content) {
    // Convert newline delimited variables into url format then import to array
    parse_str(str_replace(array("\n", "\r\n"), '&', $content), $array);
    return $array;
  }
}

/**
 * Class gapiClientLogin
 * 
 * Represents the ClientLogin authorization method
 *
 */
class gapiClientLogin extends gapiAuthMethod {
  const request_url = 'https://www.google.com/accounts/ClientLogin';

  /**
   * Authenticate Google Account with ClientLogin
   *
   * @param String $email
   * @param String $password
   * @return gapiClientLogin
   */
  protected function fetchToken($email, $password) {
    $post_variables = array(
      'accountType' => 'GOOGLE',
      'Email' => $email,
      'Passwd' => $password,
      'source' => gapi::interface_name,
      'service' => 'analytics'
    );

    $url = new gapiUrl(self::request_url);
    $response = $url->post(null, $post_variables);
    $auth_token = $this->parseBody($response['body']);

    if (substr($response['code'], 0, 1) != '2' || !is_array($auth_token) || empty($auth_token['Auth'])) {
      throw new Exception('GAPI: Failed to authenticate user. Error: "' . strip_tags($response['body']) . '"');
    }

    $this->auth_token = $auth_token['Auth'];
    return $this->auth_token;
  }

  /**
   * Return the authorization method name
   *
   * @return String
   */
  protected static function getMethodName() {
    return 'GoogleLogin';
  }

  /**
   * Returns the identifier of the token in the auth header
   *
   * @return String
   */
  protected static function getTokenName() {
    return 'auth';
  }

  /**
   * Authenticate and return a seeded gapi instance
   *
   * @param String $email
   * @param String $password
   * @return gapi
   */
  public static function authenticate($email, $password) {
    $auth_method = new gapiClientLogin();
    $auth_method->fetchToken($email, $password);
    return new gapi($auth_method);
  }
}

/**
 * Class gapiAuthSub
 * 
 * Represents the AuthSub authorization method
 *
 */
class gapiAuthSub extends gapiAuthMethod {
  const scope_url = 'https://www.google.com/analytics/feeds/';
  const request_url = 'https://www.google.com/accounts/AuthSubRequest';
  const session_token_url = 'https://www.google.com/accounts/AuthSubSessionToken';
  const revoke_token_url = 'https://www.google.com/accounts/AuthSubRevokeToken';
  const token_info_url = 'https://www.google.com/accounts/AuthSubTokenInfo';

  /**
   * Construct the URL to which the user is redirected for logging into their Google account
   *
   * @param Boolean $return_url
   */
  protected function getRequestUrl($return_url=null) {
    if ($return_url == null) {
      $return_url = gapiUrl::currentUrl();
    }

    $get_variables = array(
        'next' => $return_url,
        'scope' => self::scope_url,
        'secure' => 0,
        'session' => 1
      );

      $url = new gapiUrl(self::request_url);
      return $url->getUrl($get_variables);
  }

  /**
   * Redirect the user to the Google Accounts login page
   *
   * @param String $return_url
   */
  public function performRequest($return_url=null) {
    $url = new gapiUrl($this->getRequestUrl($return_url));
    $url->redirect();
  }

  /**
   * Using the token returned as a GET variable, fetch the session token
   *
   * @return String
   */
  public function fetchSessionToken() {
    $url = new gapiUrl(self::session_token_url);
    $response = $url->get(false, $this->generateAuthHeader($_GET['token']));
    $auth_token = $this->parseBody($response['body']);

    if (substr($response['code'], 0, 1) != '2' || !is_array($auth_token) || empty($auth_token['Token'])) {
      throw new Exception('GAPI: Failed to authenticate user. Error: "' . strip_tags($response['body']) . '"');
    }

    $this->auth_token = $auth_token['Token'];
    return $this->auth_token;
  }

  /**
   * Return token information as an associative array
   *
   * @return Array
   */
  public function getTokenInfo() {
    $url = new gapiUrl(self::token_info_url);
    $response = $url->get(false, $this->generateAuthHeader($this->auth_token));
    $info = $this->parseBody($response['body']);

    if (substr($response['code'], 0, 1) != '2' || !is_array($info)) {
      throw new Exception('GAPI: Failed to retrieve token info. Error: "' . strip_tags($response['body']) . '"');
    }

    return $info;
  }

  /**
   * Render the token invalid
   */
  public function revokeToken() {
    $url = new gapiUrl(self::revoke_token_url);
    $response = $url->get(false, $this->generateAuthHeader($this->auth_token));
    $result = $this->parseBody($response['body']);

    if (substr($response['code'], 0, 1) != '2' || !is_array($result)) {
      throw new Exception('GAPI: Failed to revoke token. Error: "' . strip_tags($response['body']) . '"');
    }

    return $result;
  }

  /**
   * Return the authorization method name
   *
   * @return String
   */
  protected static function getMethodName() {
    return 'AuthSub';
  }

  /**
   * Returns the identifier of the token in the auth header
   *
   * @return String
   */
  protected static function getTokenName() {
    return 'token';
  }

  /**
   * Generate authorization token header for all requests
   *
   * @return Array
   */
  public function generateAuthHeader($token=null) {
    if ($token == null)
      $token = $this->auth_token;
    return array('Authorization' => $this->getMethodName() . ' token=' . $token);
  }

  /**
   * Check to see if a token has been sent back through $_GET variables
   */
  public static function checkToken() {
    return self::authenticate(null, true);
  }

  /**
   * Authenticate and return a seeded gapi instance
   *
   * @return gapi
   */
  public static function authenticate($return_url=null, $check_only=false) {
    $auth_method = new gapiAuthSub();
    if (!isset($_GET['token'])) {
      // no token and we only want to check for one, so return
      if ($check_only)
        return false;
      $auth_method->performRequest();
    } else {
      $auth_method->fetchSessionToken();
    }
    return new gapi($auth_method);
  }
}

class gapiUrl {
  const http_interface = 'auto'; //'auto': autodetect, 'curl' or 'fopen'
  const interface_name = gapi::interface_name;

  private $url = null;

  /**
   * Get the current page url
   *
   * @return String
   */
  public static function currentUrl() {
    $https = $_SERVER['HTTPS'] == 'on';
    $url = $https ? 'https://' : 'http://';
    $url .= $_SERVER['SERVER_NAME'];
    if ((!$https && $_SERVER['SERVER_PORT'] != '80') &&
      ($https && $_SERVER['SERVER_PORT'] != '443')) {
      $url .= ':' . $_SERVER['SERVER_PORT'];
    }
    $url .= $_SERVER['REQUEST_URI'];
    return $url;
  }

  /**
   * Get the current page url without GET variables
   *
   * @return String
   */
  public static function currentUrlWithoutGet() {
    $url = self::currentUrl();
    return substr($url, 0, strpos($url, '?'));
  }

  public function __construct($url) {
    $this->url = $url;
  }

  /**
   * Perform http redirect
   *
   * @param Array $get_variables
   */
  public function redirect($get_variables=null) {
    header('Location: ' . $this->getUrl($get_variables));
    exit;
  }

  /**
   * Return the URL to be requested, optionally adding GET variables
   *
   * @param Array $get_variables
   * @return String
   */
  public function getUrl($get_variables=null) {
    if (is_array($get_variables)) {
      $get_variables = '?' . str_replace('&amp;', '&', urldecode(http_build_query($get_variables)));
    } else {
      $get_variables = null;
    }

    return $this->url . $get_variables;
  }

  /**
   * Perform http POST request
   * 
   *
   * @param Array $get_variables
   * @param Array $post_variables
   * @param Array $headers
   */
  public function post($get_variables=null, $post_variables=null, $headers=null) {
    return $this->request($get_variables, $post_variables, $headers);
  }

  /**
   * Perform http GET request
   * 
   *
   * @param Array $get_variables
   * @param Array $headers
   */
  public function get($get_variables=null, $headers=null) {
    return $this->request($get_variables, null, $headers);
  }

  /**
   * Perform http request
   * 
   *
   * @param Array $get_variables
   * @param Array $post_variables
   * @param Array $headers
   */
  public function request($get_variables=null, $post_variables=null, $headers=null) {
    $interface = self::http_interface;

    if (self::http_interface == 'auto')
      $interface = function_exists('curl_exec') ? 'curl' : 'fopen';

    switch ($interface) {
      case 'curl':
        return $this->curlRequest($get_variables, $post_variables, $headers);
      case 'fopen':
        return $this->fopenRequest($get_variables, $post_variables, $headers);
      default:
        throw new Exception('Invalid http interface defined. No such interface "' . self::http_interface . '"');
    }
  }

  /**
   * HTTP request using PHP CURL functions
   * Requires curl library installed and configured for PHP
   * 
   * @param Array $get_variables
   * @param Array $post_variables
   * @param Array $headers
   */
  private function curlRequest($get_variables=null, $post_variables=null, $headers=null) {
    $ch = curl_init();

    if (is_array($get_variables)) {
      $get_variables = '?' . str_replace('&amp;', '&', urldecode(http_build_query($get_variables)));
    } else {
      $get_variables = null;
    }

    curl_setopt($ch, CURLOPT_URL, $this->url . $get_variables);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //CURL doesn't like google's cert

    if (is_array($post_variables)) {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_variables);
    }

    if (is_array($headers)) {
      $string_headers = array();
      foreach ($headers as $key => $value) {
        $string_headers[] = "$key: $value";
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, $string_headers);
    }

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return array('body' => $response, 'code' => $code);
  }

  /**
   * HTTP request using native PHP fopen function
   * Requires PHP openSSL
   *
   * @param Array $get_variables
   * @param Array $post_variables
   * @param Array $headers
   */
  private function fopenRequest($get_variables=null, $post_variables=null, $headers=null) {
    $http_options = array('method'=>'GET', 'timeout'=>3);

    if (is_array($headers)) {
      $headers = implode("\r\n", $headers) . "\r\n";
    }
    else {
      $headers = '';
    }

    if (is_array($get_variables)) {
      $get_variables = '?' . str_replace('&amp;', '&', urldecode(http_build_query($get_variables)));
    }
    else {
      $get_variables = null;
    }

    if (is_array($post_variables)) {
      $post_variables = str_replace('&amp;', '&', urldecode(http_build_query($post_variables)));
      $http_options['method'] = 'POST';
      $headers = "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($post_variables) . "\r\n" . $headers;
      $http_options['header'] = $headers;
      $http_options['content'] = $post_variables;
    }
    else {
      $post_variables = '';
      $http_options['header'] = $headers;
    }

    $context = stream_context_create(array('http'=>$http_options));
    $response = @file_get_contents($this->url . $get_variables, null, $context);

    return array('body'=>$response!==false?$response:'Request failed, fopen provides no further information', 'code'=>$response!==false?'200':'400');
  }
}

/**
 * Returns the name of currently running class as it was called.
 *
 * @return String
 */
if (!function_exists('get_called_class')) {
  function get_called_class()
  {
    $bt = debug_backtrace();
    $lines = file($bt[1]['file']);
    preg_match('/([a-zA-Z0-9\_]+)::'.$bt[1]['function'].'/',
               $lines[$bt[1]['line']-1],
               $matches);
    return $matches[1];
  }
}

/**
 * Case insensitive array_key_exists function, also returns
 * matching key.
 *
 * @param String $key
 * @param Array $search
 * @return String Matching array key
 */
function array_key_exists_nc($key, $search) {
  if (array_key_exists($key, $search)) {
    return $key;
  }
  if (!(is_string($key) && is_array($search))) {
    return false;
  }
  $key = strtolower($key);
  foreach ($search as $k => $v) {
    if (strtolower($k) == $key) {
      return $k;
    }
  }
  return false;
}
