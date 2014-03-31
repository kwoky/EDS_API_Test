<?php
class EBSCOException extends Exception { }

class APIConnector{

    /**
     * Error codes defined by EDS API
     *
     * @global integer EDS_UNKNOWN_PARAMETER  Unknown Parameter
     * @global integer EDS_INCORRECT_PARAMETER_FORMAT  Incorrect Parameter Format
     * @global integer EDS_INCORRECT_PARAMETER_FORMAT  Invalid Parameter Index
     * @global integer EDS_MISSING_PARAMETER  Missing Parameter
     * @global integer EDS_AUTH_TOKEN_INVALID  Auth Token Invalid
     * ...
     */
    const EDS_UNKNOWN_PARAMETER          = 100;
    const EDS_INCORRECT_PARAMETER_FORMAT = 101;
    const EDS_INVALID_PARAMETER_INDEX    = 102;
    const EDS_MISSING_PARAMETER          = 103;
    const EDS_AUTH_TOKEN_INVALID         = 104;
    const EDS_INCORRECT_ARGUMENTS_NUMBER = 105;
    const EDS_UNKNOWN_ERROR              = 106;
    const EDS_AUTH_TOKEN_MISSING         = 107;
    const EDS_SESSION_TOKEN_MISSING      = 108;
    const EDS_SESSION_TOKEN_INVALID      = 109;
    const EDS_INVALID_RECORD_FORMAT      = 110;
    const EDS_UNKNOWN_ACTION             = 111;
    const EDS_INVALID_ARGUMENT_VALUE     = 112;
    const EDS_CREATE_SESSION_ERROR       = 113;
    const EDS_REQUIRED_DATA_MISSING      = 114;
    const EDS_TRANSACTION_LOGGING_ERROR  = 115;
    const EDS_DUPLICATE_PARAMETER        = 116;
    const EDS_UNABLE_TO_AUTHENTICATE     = 117;
    const EDS_SEARCH_ERROR               = 118;
    const EDS_INVALID_PAGE_SIZE          = 119;
    const EDS_SESSION_SAVE_ERROR         = 120;
    const EDS_SESSION_ENDING_ERROR       = 121;
    const EDS_CACHING_RESULTSET_ERROR    = 122;


    /**
     * HTTP status codes constants
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    const HTTP_OK                    = 200;
    const HTTP_BAD_REQUEST           = 400;
    const HTTP_NOT_FOUND             = 404;
    const HTTP_INTERNAL_SERVER_ERROR = 500;



    private static $end_point = "http://eds-api.ebscohost.com/edsapi/rest";
	//private static $end_point = "http://lh2cc.net/dse/kc/testing/proxy.php";	
	//private static $end_point = "http://localhost:81/ebsco/proxy.php";	
    private static $AuthenticationEndPoint ="https://eds-api.ebscohost.com/Authservice/rest";        
    //private static $AuthenticationEndPoint ="http://localhost:81/ebsco/auth_proxy.php";        
	
	private $UID;
	private $password;
    private $interfaceId;
    private $profile;
    private $orgId;
    private $debug;
	
	public $AuthenticationToken;
	public $SessionToken;
	
	//Update the constructor to use your API profile
	public function __construct($UID,$password,$interfaceId,$profile,$orgId,$debug)
    {        
		$this -> UID=$UID;
		$this -> password=$password;
		$this -> interfaceId = $interfaceId;
		$this -> profile = $profile;
		$this -> orgId = $orgId;
		$this -> debug = $debug;
    }

	public function buildHeader(){
		//TODO add in error checking if tokens have expired
		return $headers = array(
            'Content-Type: application/xml',
            'Accept: application/xml' ,		
            'x-authenticationToken: ' . $this->AuthenticationToken,
            'x-sessionToken: ' . $this->SessionToken,
			'Host: localhost'
        );
	}
	
	public function requestAuthenticationToken()
    {
        $url = self::$AuthenticationEndPoint.'/UIDAuth';
		
        // Add the body of the request.
        $params =<<<BODY
<UIDAuthRequestMessage xmlns="http://www.ebscohost.com/services/public/AuthService/Response/2012/06/01">
    <UserId>{$this->UID}</UserId>
    <Password>{$this->password}</Password>
    <InterfaceId>{$this->interfaceId}</InterfaceId>
</UIDAuthRequestMessage>
BODY;

        // Set the content type to 'application/xml'.
        $headers = array(
            'Content-Type: application/xml',
            'Conent-Length: ' . strlen($params)
        );

        $response = $this->request($url, $params, $headers, 'POST');
		$this->AuthenticationToken = $response->AuthToken;
        return $response;
    }
	
    public function requestSessionToken($guest)
    {
        //$url = self::$end_point . '/CreateSession';
		$url = "http://eds-api.ebscohost.com/edsapi/rest/CreateSession";
	 
		// Add the HTTP query parameters
        $params = array(
            'profile' => $this->profile,
            'org'     => $this->orgId,
            'guest'   => $guest
        );
		
		$headers = array(
                'Content-Type: application/xml',
                'Accept: application/xml' ,		
                'x-authenticationToken: ' . $this->AuthenticationToken,
				'Host: localhost'
        );
		
        $params = http_build_query($params);
        $response = $this->request($url, $params, $headers);		
		$this->SessionToken = $response->SessionToken;

        return $response;
    }
    
    public function requestEndSessionToken($headers, $sessionToken){
        $url = self::$end_point.'/endsession';
        
        // Add the HTTP query parameters
        $params = array(
            'sessiontoken'=>$sessionToken
        );
        $params = http_build_query($params);              
        $this->request($url,$params,$headers);
    }

    public function requestInfo($params, $headers)
    {
        $url = self::$end_point . '/Info';
        $response = $this->request($url, $params, $headers);
        return $response;
    }

    public function requestSearch($params, $headers)
    {
        $url = self::$end_point . '/Search';		
        $response = $this->request($url, $params, $headers);
        return $response;
    }

    public function requestRetrieve($params, $headers)
    {
        $url = self::$end_point . '/Retrieve';
        $response = $this->request($url, $params, $headers);
        return $response;
    }

    /**
     * Send an HTTP request and inspect the response
     *
     * @param string $url         The url of the HTTP request
     * @param array  $params      The parameters of the HTTP request
     * @param array  $headers     The headers of the HTTP request
     * @param array  $body        The body of the HTTP request
     * @param string $method      The HTTP method, default is 'GET'
     *
     * @return object             SimpleXml
     * @access protected
     */
    protected function request($url, $params = null, $headers = null, $method = 'GET') 
    {
        $log = fopen('curl.log', 'w'); // for debugging cURL
        $xml = false;
        //$return = false;

        // Create a cURL instance
        $ch = curl_init();

        // Set the cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $log);  // for debugging cURL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Termporary

        // Set the query parameters and the url
        if (empty($params)) {
            // Only Info request has empty parameters
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            // GET method
            if ($method == 'GET') {
                $url .= '?' . $params;                
                curl_setopt($ch, CURLOPT_URL, $url);
            // POST method
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        } 

        // Set the header
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Send the request
        $response = curl_exec($ch);
        
		//Log the transfer and performance details if in Debug Mode
		if ($this->debug){
			//var_dump(curl_getinfo($ch));
			$transferInfo = curl_getinfo($ch);
			
			/*
			echo "Request         : ".$transferInfo["url"]."<br>";
			echo "Total time      : ".($transferInfo["total_time"]*1000)." ms<br>";
			echo "DNS Lookup time :".$transferInfo["namelookup_time"]."<br>";
			echo "Connect time    :".$transferInfo["connect_time"]."<br>";
			echo "Transfer time   :".$transferInfo["pretransfer_time"]."<br>";
			echo "Start time      :".$transferInfo["starttransfer_time"]."<br>";*/
			
			echo "<b> ------------------- Transfer information ------------------- </b>";	
			echo "<pre>"; print_r($transferInfo);echo "</pre>";			
			echo "<b> ------------------------------------------------------------ </b>";	
			echo "<br/><b>API Response Time for test case : <u>".(($transferInfo["starttransfer_time"]-$transferInfo["connect_time"])*1000)." ms</u></b>";	
		}
		
        //Save XML file for debug mode      
        if(strstr($url,'Search')){
            $_SESSION['resultxml'] = $response;
        }
        if(strstr($url,'Retrieve')){
           $_SESSION['recordxml'] = $response;
        }
        // Parse the response
        // In case of errors, throw 2 type of exceptions
        // EBSCOException if the API returned an error message
        // Exception in all other cases. Should be improved for better handling
        if ($response === false) {
            fclose($log); // for debugging cURL
            throw new Exception(curl_error($ch));
            curl_close($ch);
        } else {         
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            fclose($log);  // for debugging cURL
            curl_close($ch);
            switch ($code) {
                case self::HTTP_OK:
                    //echo $response;
					$xml = simplexml_load_string($response);
                    if ($xml === false) {
                         throw new Exception('Error while parsing the response.');
                    } else {                    
                         return $xml;
                    }
                    break;
                case self::HTTP_BAD_REQUEST:
                    $xml = simplexml_load_string($response);
                    if ($xml === false) {
                         throw new Exception('Error while parsing the response.');
                    } else {
                        // If the response is an API error
                        $error = ''; $code = 0;
                        $isError = isset($xml->ErrorNumber) || isset($xml->ErrorCode);
                        if ($isError) {
                            if (isset($xml->DetailedErrorDescription) && !empty($xml->DetailedErrorDescription)) {
                                $error = (string) $xml->DetailedErrorDescription;
                            } else if (isset($xml->ErrorDescription)) {
                                $error = (string) $xml->ErrorDescription;
                            } else if (isset($xml->Reason)) {
                                $error = (string) $xml->Reason;
                            }
                            if (isset($xml->ErrorNumber)) {
                                $code = (integer) $xml->ErrorNumber;
                            } else if (isset($xml->ErrorCode)) {
                                $code = (integer) $xml->ErrorCode;
                            }
                            throw new EBSCOException($error, $code);
                        } else {
                            throw new Exception('The request could not be understood by the server 
                            due to malformed syntax. Modify your search before retrying.');
                        }
                    }
                    break;
                case self::HTTP_NOT_FOUND:
                    throw new Exception('The resource you are looking for might have been removed, 
                        had its name changed, or is temporarily unavailable.');
                    break;
                case self::HTTP_INTERNAL_SERVER_ERROR:
                    throw new Exception('The server encountered an unexpected condition which prevented 
                        it from fulfilling the request.');
                    break;
                // Other HTTP status codes
                default:
                    throw new Exception('Unexpected HTTP error.');
                    break;
            }
        }
    }
}

?>