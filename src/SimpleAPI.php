<?php
/**
 * Request Factory - Singleton
 *
 * Usage:
 * ------ 
 * $rest = SimpleAPI\RequestFactory::getRequest();
 *
 * @author <mitchseymour@gmail.com>
 * @version 0.0.1
 */
namespace SimpleAPI;

class RequestFactory {
	
	/**
	 * @var static Request $request
	 * 
	 * A Request object
	 */
	private static $request;
	
	/**
	 * Private Constructor - don't allow instantiation!
	 */
	private function __construct(){}
	
	/**
	 * Get request
	 *
	 * The method used for retrieving an instance of the request class
	 *
	 * @return Request $object - A Request instance
	 */
	public static function getRequest(){
	
		if (!self::$request)
			self::$request = new Request();
			
		return self::$request;
	}
}
 
/**
 * Request class - Great for APIs
 *
 * Usage:
 * ------
 * // Get a request object
 * $request = RequestFactory::getRequest();
 * ------------------------------------------------------------------------
 *
 * // enforce an API key
 * ------------------------------------------------------------------------
 * $request->apikey(function($key){ 
 * 		// function for validating api key 
 *      // return true if the key is valid, and false if not
 * });
 *
 * // Performs the necessary checks for params, param types, api key,
 * // and coeerces the values to the type specified after the | character
 * // (optional)
 * ------------------------------------------------------------------------
 * $request->expecting('event|integer', 'data|array');
 *
 * // Getting params
 * ------------------------------------------------------------------------
 * $event = $request->param('event');
 *
 * // Setting params
 * ------------------------------------------------------------------------
 * $request->param('event', 'hithere');
 *
 * // Send JSON responses and HTTP status codes with a single method
 * ------------------------------------------------------------------------
 * $request->respond(200, 'OK');
 * $request->respond(405, array('invalid parameters' => '123'));
 *
 *
 * @author <mitchseymour@gmail.com>
 * @version 0.0.1
 */

$GLOBALS['requestStart'] = microtime(true);
		
class Request {

	/**
	 * @var boolean $cors
	 * 
	 * Whether or not Cross-Origin Resource Sharing should be enabled
	 */
	private $cors = false;
	
	/**
	 * @var array $headers
	 * 
	 * An array of HTTP headers that were passed in the current request
	 */
	private $headers = array();

	/**
	 * @var string $method
	 * 
	 * The verb used for the current request (GET, POST, PUT, DELETE, etc)
	 */
	private $method = 'GET';
	
	/**
	 * @var array $params
	 * 
	 * An array of parameters that were passed in the current request
	 */
	private $params = array();
	
	/**
	 * Constructor
	 *
	 * Sets the header info and request method
	 */
	public function __construct(){
		
		$this->headers = $this->headers();
		$this->method  = $_SERVER['REQUEST_METHOD'];
		
		$method = strtolower($this->method);
		
		switch ($method){
			
			case 'get'  : $this->params = $_GET; break;
			case 'post' : $this->params = $_POST; break;
			default: $this->params = $_REQUEST; break;
			
		}
		
	}
	
	/**
	 * Remove the protocol from a URL string
	 *
	 * @param string $url - The URL to remove the protocol from
	 * @return string $url - URL with the protocol removed
	 */
	public static function removeProtocol($url){
	
		$protocols = array('file', 'ftp', 'http', 'https', 'ldap', 'ldaps');

		foreach ($protocols as $index => $protocol) {
			$protocols[$index] = $protocol . '://';
		}

		$url = str_replace($protocols, '', $url);
		return $url;

    }
	
	/**
	 * CORS - enable/disabled Cross-Origin Resource Sharing
	 *
	 * @param boolean $enable
	 * @return $this - Returns the instantiated object to allow for method chaining
	 */
	public function cors($bool=true){
	
		$this->cors = (boolean) $bool;
		return $this;
	
	}
	/**
	 * Get the headers passed in the current request
	 *
	 * @return array $headers -An array of HTTP headers
	 */
	public function headers(){
			
		if ($this->headers){
			return $this->headers;
		}
		
		if (!function_exists('getallheaders')) {
		
           $headers = ''; 
		   
			foreach ($_SERVER as $name => $value){ 
			   if (substr($name, 0, 5) == 'HTTP_') { 
				   $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
			   }
			}
			
			$this->headers = $headers; 
		
		} else {
		
			$this->headers = getallheaders();
		}
		
		return $this->headers;
	}
	
	/**
	 * Get the value for a request header
	 *
	 * @param string $key - The name of the header
	 * @return mixed $value
	 */
	public function header($key){
	
		$headers = $this->headers();
		
		if (isset($headers[$key])){
			return $headers[$key];
		}
		
		return false;
	}
	
	/**
	 * Get the request method
	 *
	 * @return $string $method
	 */
	public function method(){
	
		return $this->method;
	}
	
	/**
	 * POST - make sure the request came in as a POST
	 *
	 * @return $this - Returns the instantiated object to allow for method chaining
	 */
	public function post($message=false){
		
		$method = strtoupper($this->method);
				
		if (is_object($message) && get_class($message) == 'Closure'){
			
			if ($method !== 'POST'){	
				return $this;	
			}
			
			return call_user_func_array($message, array($this));
		
		} else if (!$message){
			$message = $method . ' not supported';
		}
		
		if ($method !== 'POST'){
			$this->respond(405, $message);
			die;
		}
		
		return $this;
	}
	
	/**
	 * GET - make sure the request came in as a GET
	 *
	 * @return $this - Returns the instantiated object to allow for method chaining
	 */
	public function get($message='GET not supported'){

		$method = strtoupper($this->method);
		
				
		if (is_object($message) && get_class($message) == 'Closure'){
			
			if ($method !== 'GET'){	
				return $this;	
			}
			
			return call_user_func_array($message, array($this));
		
		} else if (!$message){
			$message = $method . ' not supported';
		}
		
		if ($method !== 'GET'){
			$this->respond(405, $message);
			die;
		}
		
		return $this;
	}
	
	/**
	 * Set the response code
	 *
	 * @param integer $code - The status code
	 * @return $this - Returns the instantiated object to allow for method chaining
	 */
	public function status($code=200){

		http_response_code($code);
		return $this;
		
	}
	
	/**
	 * Respond
	 *
	 * @param integer $code - The status code
	 * @param mixed $message - A message to be echoed out to the client
	 * @return $this - Returns the instantiated object to allow for method chaining
	 */
	public function respond($code, $message='', $clean=true){
		

		$this->status($code);
		$response = array('code' => $code, 'response_time_seconds' => round(microtime(true) - $GLOBALS['requestStart'], 2));
		
		if (is_array($message)){
		
			foreach ($message as $key => $val){
				$response[$key] = $val;
			}
		
		} else {
		
			$response['message'] = $message;
		
		}
		
		// clean the buffer first
		ob_get_clean();

		header('Content-Type: application/json');
		
		if ($this->cors){
			// enable Cross-Origin Resource Sharing
			header('Access-Control-Allow-Origin: *');
		}
		echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		die;
		
	}
	
	/**
	 * Require an API Key
	 *
	 * API Keys are passed in the Authorization header, or, optionally, in the apikey parameter. 
	 *  This method checks to make sure that the parameter is passed, and allows the client to 
	 * specify a callback for validating the apikey that was provided in the request.
	 *
	 * @param Callable $cb - A callback to be used for validating the apikey. The apikey
	 *						 is passed as the first argument to this function.
	 *
	 * @return $this - Returns the instantiated object to allow for method chaining
	 */
	public function apikey($cb=false){
		
		$tokenHeader = $this->header('Authorization');
		$parts = explode('Token token=', $tokenHeader);
		
		if (count($parts) == 2 && trim($parts[1])){
			
			$apikey = trim($parts[1]);
		
		} else {
		
			$apikey = $this->param('apikey');
		
		}
		
		
		if (!$apikey){
			return $this->respond(403, 'unauthorized');
		}
		
		if ($cb){
		
			if (!is_callable($cb)){
				return $this->respond(500);
			}
			
			// delegate the validation
			$validated = call_user_func_array($cb, array($apikey));
			
			if(!$validated){
				return $this->respond(403, 'unauthorized');
			}
		}
		
		return $this;
	}
	
	/**
	 * Get/Set param
	 *
	 * A simple method for getting and setting parameter values. This takes care of
	 * the boilerplate tasks of checking whether or not the parameter exists,
	 * type casting, and retrieving.
	 *
	 * @param string $key - The name of the parameter to retrieve
	 * @return mixed $values - The parameter value if it exists, or null
	 */	
	public function param($key, $val=null){
		
		if ($val !== null){
			$this->params[$key] = $val;
			return $val;
		}
		
		$search = $this->params;
		
		if (isset($search[$key])){
			
			if (is_array($search[$key])){
				return array_filter($search[$key]);
			}
			return $search[$key];
		}
		
		return null;
	}
	
	public function params(){
		$params = $this->params;
		unset($params['_q']);
		return $params;
	}
	
	public function url(){

		return sprintf(
			"%s://%s%s",
			isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
			$_SERVER['HTTP_HOST'],
			rtrim(explode('?', $_SERVER['REQUEST_URI'])[0], '/')
		);
	}

	/**
	 * Convert a value to boolean
	 *
	 * We use the more verbose filter_var so that the 'false' string,
	 * which sometimes appears in ajax requests, evaluates to false.
	 *
	 * @return boolean $bool
	 */
	public function toBool($val){
		return filter_var($val, FILTER_VALIDATE_BOOLEAN);
	}
	
	/**
	 * See self::toBool()
	 */
	public function toBoolean($val){
		return $this->toBool($val);
	}
	
	/**
	 * Insert params into SimpleDB datastore: To do !
	 */
	public function insert(SimpleDB $dbh, $table, array $lookup=array()){
		
		$columns = array();
		$toCSV   = array();
		
		// to do
		foreach ($this->params as $param => $val){
			
			$key = isset($lookup[$param]) ? $lookup[$param] : $param;
			$columns[] = $key;
			$toCSV[]= $val;
			
		}
		
		die('To do');
	}
	
	/**
	 * Insert Repeat
	 *
	 * Inserts param values into a SimpleDB datastore. But the params could potentially
	 * be mapped to multiple rows.
	 *
	 */
	public function insertRepeat($repeaterParam, SimpleDB $dbh, $table, array $lookup=array(), $idCol=false){
		
		$columns = array();
		$toCSV   = array();
		
		$repeater = $this->param($repeaterParam);
		
		if (!is_array($repeater)){
			return false;
		}
		
		foreach ($repeater as $arr){
		
			$a = $this->params;
			unset($a[$repeaterParam]);
			$merged = array_merge($a, $arr);
			
			// make sure the null values are applied
			foreach ($merged as $index => $val){
				
				if ($val == null)
					$merged[$index] = "\N";
				
			}
			
			$toCSV[] = array_values($merged);
			
			if (!$columns)
				$columns = array_keys($merged);
			
		}
		
		$columnLookup = array();
		
		foreach ($columns as $column){
			isset($lookup[$column]) ? $columnLookup[] = $lookup[$column] : $columnLookup[] = $column;
		}
		
		$file = $dbh->getTemporaryFile('api_insert_');
		$dbh->array2CSV($file, $toCSV);
		$extra = false;
		
		if ($idCol){
			
			$a = array_flip($columnLookup);
			unset($a[$idCol]);
			$extra = "on duplicate key update ";
			foreach ($a as $col => $index){
				$extra .= "{$col}=values({$col}),";
			}
			
			$extra = rtrim($extra, ',');
			
		}
		
		return $dbh->loadDataInFileViaTempTable($file, $table, $columnLookup, $extra);
	
	}
	
	/**
	 * Expecting
	 *
	 * A list of params, with optional syntactic sugar for specifying
	 * type casts, optional values, etc
	 *
	 * @return $this - Returns the instantiated object to allow for method chaining
	 */
	public function expecting(){
		
		$args   = func_get_args();
		$search = $this->params;
		$errors = array();
		
		foreach ($args as $param){
			
			$optional = strpos($param, '?') !== false;
			$default = false;
			
			if ($optional){
				$ex = explode('?', explode('|', $param)[0]);
				$default = isset($ex[1]) && $ex[1] ? $ex[1] : false;
			}
			
			// the | can passed to enforce a type. we need to remove it
			if (strpos($param, '|') !== false){
				
				// a type was specified
				list($paramStr, $type) = explode('|', $param);
				
			} else {
				
				// a type was not specified
				$paramStr = $param;
				$type = false;
				
			}
			
			// make sure the parameter was set
			if (!isset($search[$paramStr]) || (!$search[$paramStr] && $type !== 'boolean' && $type !== 'bool' && ($type !== 'int' && $search[$paramStr] == 0))){
				
				if ($optional){
					
					if ($type)
						settype($default, $type);
					
					unset($search[$paramStr]);
					$search[$ex[0]] = isset($search[$ex[0]]) ? $search[$ex[0]] == 'false' && $type !== 'string' ? null :  $search[$ex[0]] : $default;
					continue;
					
				} else {
					$errors['missing params'][] = $paramStr;
					continue;
				}
			
			}
			
			$beforeType = gettype($search[$paramStr]);
			
			// if a type was specified, make sure it can be coerced to this type
			if ($type && $type !== 'array' && $beforeType !== $type){
				
				// type doesn't match, try to coeerce it
				$before = $search[$paramStr];
				settype($search[$paramStr], $type);
				
				$coerced = (string) $before == (string) $search[$paramStr];

				if (!$coerced){
					$errors['invalid type'][] = array('param' => $paramStr, 'expecting' => $type, 'received' => $beforeType, 'value' => $before);
					continue;
				}
	
				
			} else if ($type && $type == 'array'){
				
				if (!is_array($search[$paramStr])){
					$arr = json_decode($search[$paramStr], true);
				} else {
					$arr = $search[$paramStr];
				}
				
				if (!is_array($arr)){
					$errors['invalid type'][] = array('param' => $paramStr, 'expecting' => 'array', 'received' => $beforeType);
					continue;
				}
				
				if (!$arr){
					$errors['missing params'][] = $paramStr;
					continue;
				}
				
				$search[$paramStr] = $arr;
								
			}
		}
		
		$this->params = $search;
				
		if ($errors){
			$this->respond(422, $errors);
		}
		
		return $this;
	}
	
	/**
	 * Helper method used to generate unique tokens
	 *
	 * @url http://stackoverflow.com/a/13733588/1056679
	 */
	private function crypto_rand_secure($min, $max){
		$range = $max - $min;
		if ($range < 1) return $min; // not so random...
		$log = ceil(log($range, 2));
		$bytes = (int) ($log / 8) + 1; // length in bytes
		$bits = (int) $log + 1; // length in bits
		$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter; // discard irrelevant bits
		} while ($rnd >= $range);
		return $min + $rnd;
	}

	/**
	 * Get a unique identifier for the current request
	 *
	 * @url http://stackoverflow.com/a/13733588/1056679
	 */
	public function token($length=30){
		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789";
		$max = strlen($codeAlphabet) - 1;
		for ($i=0; $i < $length; $i++) {
			$token .= $codeAlphabet[$this->crypto_rand_secure(0, $max)];
		}
		return $token;
	}
	
	/**
	 * Alias for the token method
	 */
	public function id($length=30){
		
		return $this->token($length);
		
	}

}