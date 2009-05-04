<?php
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
 * @version 0.1 beta
 * 
 */

class gapi
{
  const client_login_url = 'https://www.google.com/accounts/ClientLogin';
  const account_data_url = 'https://www.google.com/analytics/feeds/accounts/default';
  const report_data_url = 'https://www.google.com/analytics/feeds/data';
  
  const interface_name = 'GAPI-0.1beta';
  
  private $auth_token = null;
  
  /**
   * Constructor function for all new gapi instances
   * 
   * Set up authenticate with Google and get auth_token
   *
   * @param String $email
   * @param String $password
   * @param String $token
   * @return gapi
   */
  public function __construct($email, $password, $token=null)
  {
    if($token !== null)
    {
      $this->auth_token = $token;
    }
    else 
    {
      $this->authenticateUser($email,$password);
    }
  }
  
  /**
   * Return the auth token, used for storing the auth token in the user session
   *
   * @return String
   */
  public function getAuthToken()
  {
    return $this->auth_token;
  }
  
  /**
   * Request account data from Google Analytics
   * 
   * $parameters should be in key => value format
   *
   * @param Array $parameters
   */
  public function requestAccountData($parameters=null)
  {
    $response = $this->httpRequest(gapi::account_data_url, $parameters, null, $this->generateAuthHeader());
    
    if(substr($response['code'],0,1) == '2')
    {
      return simplexml_load_string($response['body']);
    }
    else 
    {
      throw new Exception('GAPI: Failed to request account data. Error: "' . $response['body'] . '"');
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
   * @param Array $parameters
   */
  public function requestReportData($report_id, $parameters=null)
  {
    if(is_array($parameters))
    {
      $parameters['ids'] = 'ga:' . $report_id;
    }
    else 
    {
      $parameters = array('ids'=>'ga:' . $report_id);
    }
    
    $response = $this->httpRequest(gapi::report_data_url, $parameters, null, $this->generateAuthHeader());
    
    //HTTP 2xx
    if(substr($response['code'],0,1) == '2')
    {
      return simplexml_load_string($response['body']);
    }
    else 
    {
      throw new Exception('GAPI: Failed to request report data. Error: "' . $response['body'] . '"');
    }
  }
  
  /**
   * Authenticate Google Account with Google
   *
   * @param String $email
   * @param String $password
   */
  protected function authenticateUser($email, $password)
  {
    $post_variables = array(
      'accountType' => 'GOOGLE',
      'Email' => $email,
      'Passwd' => $password,
      'source' => gapi::interface_name,
      'service' => 'analytics'
    );
    
    $response = $this->httpRequest(gapi::client_login_url,null,$post_variables);
    
    //Convert newline delimited variables into url format then import to array
    parse_str(str_replace(array("\n","\r\n"),'&',$response['body']),$auth_token);
    
    if(substr($response['code'],0,1) != '2' || !is_array($auth_token) || empty($auth_token['Auth']))
    {
      throw new Exception('GAPI: Failed to authenticate user. Error: "' . $response['body'] . '"');
    }
    
    $this->auth_token = $auth_token['Auth'];
  }
  
  /**
   * Generate authentication token header for all requests
   *
   * @return Array
   */
  protected function generateAuthHeader()
  {
    return array('Authorization: GoogleLogin auth=' . $this->auth_token);
  }
  
  /**
   * Perform http request
   * 
   * Currently using CURL, but should be upgraded to support
   * other methods, like fopen with stream_context_create
   *
   * @todo Upgrade to support other request methods
   * @param Array $get_variables
   * @param Array $post_variables
   * @param Array $headers
   */
  protected function httpRequest($url, $get_variables=null, $post_variables=null, $headers=null)
  {
    $ch = curl_init();
    
    if(is_array($get_variables))
    {
      $get_variables = '?' . str_replace('&amp;','&',urldecode(http_build_query($get_variables)));
    }
    else 
    {
      $get_variables = null;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url . $get_variables);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //CURL doesn't like google's cert
    
    if(is_array($post_variables))
    {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_variables);
    }
    
    if(is_array($headers))
    {
      curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    }
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    return array('body'=>$response,'code'=>$code);
  }
}