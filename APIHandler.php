<?

class APIHandler{

  private $dev_token = "INSERT_YOUR_TOKEN"; 
  // tokens API url
  private $url_token = "https://accounts.google.com/o/oauth2/token";
  // Refresh token recovery var
  private $code = "INSERT_YOUR_AUTHORIZATION_CODE";
  private $garant_code = "authorization_code";
  private $redirect_uri = "urn:ietf:wg:oauth:2.0:oob";
  // Auth token recovery var
  private $grant = "refresh_token";
  ////you can use the function in this class to get it, the refresh token will not change
  private $refresh_token = "INSERT_YOUR_REFRESH_TOKEN";
  private $client_id = INSERT_YOUR_CLIENT_ID"";
  private $client_secret = "INSERT_YOUR_CLIENT_SECRET"
  // Operations var
  private $url_downloadReport = "https://adwords.google.com/api/adwords/reportdownload/v201806";
  private $url_customers = "https://adwords.google.com/api/adwords/mcm/v201806/ManagedCustomerService?wsld";
  private $manager_account_id = "INSERT_YOUR_MANAGER_ACCOUNT_ID"
  private $access_token = null;

  // =======================================
  // ---------------------------------------
  //   PUBLIC METHODS
  // ---------------------------------------
  // =======================================

  function __construct()
  {
    $this->access_token = $this->getAccessToken();
  }
  /* ***************************************
  *
  * PERFORM TOKEN REQUEST
  *
  * Performs the http post request to $this->url_token
  * Used to get both access and refresh token depending
  * on the @param $data.
  *
  * @param String $data the post data to send
  *
  * @return Mixed json decodification of API response
  *
  * ***************************************/
  private function performTokenRequest($data)
  {
    // Set up curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->url_token);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);

    // Execute the request
    $result = curl_exec($ch);
    // Close curl
    curl_close($ch);
    // Decode response
    $return = json_decode($result);

    if($return === false)
    {
      return "fail";
    }

    return $return;
  }
  /* ***************************************
  *
  * GENERATE REPORT XML
  *
  * Create a well formatted xml report definition
  * to downloadReport API
  *
  * @param String $reportName the name of the new report
  * @param String $reportType the type of the report (see google adWords docs)
  * @param String $dataRange data range filter (see google adWords docs)
  *  @param [String] $fields wanted headers
  *  @param String $predicate XML wanted predicate (see google adWords docs)
  *
  * @return String the XML report definition
  *
  * ***************************************/
  public function generateReportXml($reportName, $reportType, $dataRange, $fields, $predicate = null)
  {
    $reportDefinition = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><reportDefinition xmlns=\"https://adwords.google.com/api/adwords/cm/v201806\">";
    $reportDefinition .= "<selector>";
    foreach($fields as $field)
    {
      $reportDefinition .= "<fields>$field</fields>";
    }
    if($predicates)
    {
      $reportDefinition .= "<predicates>";
      $reportDefinition .= $predicate;
      $reportDefinition .= "</predicates>";
    }
    $reportDefinition .= "</selector>";
    $reportDefinition .= "<reportName>$reportName</reportName>";
    $reportDefinition .= "<reportType>$reportType</reportType>";
    $reportDefinition .= "<dateRangeType>$dataRange</dateRangeType>";
    $reportDefinition .= "<downloadFormat>XML</downloadFormat>";
    $reportDefinition .= "</reportDefinition>";
    return $reportDefinition;
  }
  /* ***************************************
  *
  * GET REPORT
  *
  * Gets the report data from the API
  *
  * @param String $reportDefinition the xml report definition
  * @param String $client_customer_id account id 
  *
  * @return SimpleXMLElement the decoded report
  *
  * ***************************************/
  public function getReport($reportDefinition, $client_customer_id)
  {
    $params = array("__rdxml" => ($reportDefinition));
    $headers = [
    "Authorization: Bearer $this->access_token", 
    "developerToken: $this->dev_token", 
    "clientCustomerId: $client_customer_id",
    "Expect: 100-continue",
    "Accept: /",
    ];

    $result = $this->genericCurlRequest($this->url_downloadReport, $params, $headers); 

    return simplexml_load_string($result);
    }
  /* ***************************************
  *
  * GET CUSTOMERS ID
  *
  * Gets all the not manager account refer to
  * $this->manager_account_id
  *
  * @return SimpleXMLElement the API decoded response
  *
  * ***************************************/
  public function getCustomersId()
  {
    $soap = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>
    <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
      <soapenv:Header>
        <ns1:RequestHeader xmlns:ns1=\"https://adwords.google.com/api/adwords/mcm/v201806\" soapenv:mustUnderstand=\"0\">
          <ns2:developerToken xmlns:ns2=\"https://adwords.google.com/api/adwords/cm/v201806\">$this->dev_token</ns2:developerToken>
          <ns3:userAgent xmlns:ns3=\"https://adwords.google.com/api/adwords/cm/v201806\">AdWords (AwApi-Java, AdWords-Axis/3.8.0, Common-Java/3.8.0, Axis/1.4, Java/1.8.0_91, maven)</ns3:userAgent>
          <ns4:validateOnly xmlns:ns4=\"https://adwords.google.com/api/adwords/cm/v201806\">false</ns4:validateOnly>
          <ns5:partialFailure xmlns:ns5=\"https://adwords.google.com/api/adwords/cm/v201806\">false</ns5:partialFailure>
          <ns6:clientCustomerId xmlns:ns6=\"https://adwords.google.com/api/adwords/cm/v201806\">$this->manager_account_id</ns6:clientCustomerId>
        </ns1:RequestHeader>
      </soapenv:Header>
      <soapenv:Body>
        <get xmlns=\"https://adwords.google.com/api/adwords/mcm/v201806\">
          <serviceSelector>
            <fields>CustomerId</fields>
            <predicates>
              <field>CanManageClients</field>
              <operator>EQUALS</operator>
              <values>false</values>
            </predicates>
          </serviceSelector>
        </get>
      </soapenv:Body>
    </soapenv:Envelope>";
    $soap = trim($soap);

    $result = $this->genericCurlRequest($this->url_customers, $soap, ["Authorization: Bearer $this->access_token"]); 

    $result = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $result);
    return simplexml_load_string($result);
  }

  // =======================================
  // ---------------------------------------
  //   PRIVATE METHODS
  // ---------------------------------------
  // =======================================

  /* ***************************************
  *
  * GET ACCESS TOKEN
  *
  * Set the post data to send to the API to
  * recover a valid access token.
  *
  * @return String the new API access token
  *
  * ***************************************/
  private function getAccessToken()
  {
    $data = "refresh_token=$this->refresh_token&client_id=$this->client_id&client_secret=$this->client_secret&grant_type=$this->grant";
    return $this->performTokenRequest($data)->access_token;
  }
  /* ***************************************
  *
  * GET REFRESH TOKEN
  *
  * Set the post data to send to the API to
  * recover a valid refresh token.
  *
  * @return String the new API refresh token
  *
  * ***************************************/
  private function getRefresToken()
  {
    $data = "code=$this->code&client_id=$this->client_id&client_secret=$this->client_secret&redirect_uri=$this->redirect_uri&grant_type=$this->grant_code";
    return $this->performTokenRequest($data)->refresh_token;
  }
  /* ***************************************
  *
  * GENERIC CURL REQUEST
  *
  * Perform curl request with "standard" options
  *
  * @param String $url the request url
  * @param Mixed $data the post data to send
  * @param [String] $headers request headers
  *
  * @return Mixed curl_exec result
  *
  * ***************************************/
  private function genericCurlRequest($url, $data, $headers)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
    curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
  }
}
