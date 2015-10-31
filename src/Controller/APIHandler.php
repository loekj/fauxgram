<?php
// app/src/Controller/AdminController.php
namespace Controller;
	use Silex\Application;
	use Symfony\Component\HttpFoundation\Request;
	use APIException\APIException as APIException;
	use PDO;

	# cannot have an instance
	abstract class APIHandler {
		protected $method = '';
		protected $endpoint = '';
		/**
		 * Property: verb
		 * An optional additional descriptor about the endpoint, used for things that can
		 * not be handled by the basic methods. eg: /files/process
		 */
		protected $verb = '';
		/**
		 * Property: args
		 * Any additional URI components after the endpoint and verb have been removed, in our
		 * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
		 * or /<endpoint>/<arg0>
		 */
		protected $args = Array();
		/**
		 * Property: file
		 * Stores the input of the PUT request
		 */
		 protected $file = Null;

		 /** Silex app for nice functionality
		 * like Doctrine/dbal
		 */
		 #protected $app = Null;
		 protected $db = Null;

		/**
		 * Constructor: __construct
		 * Allow for CORS, assemble and pre-process the data
		 */
		public function __construct($request) {
	        header("Access-Control-Allow-Orgin: *");
	        header("Access-Control-Allow-Methods: *");
	        header("Content-Type: application/json");
			

			#$this->args = explode('/', rtrim($request, '/'));
	        #$this->endpoint = array_shift($this->args);
	        $this->endpoint = array_shift($request);
	        // $this->args = $request;
	        // if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
	        //     $this->verb = array_shift($this->args);
	        // }

	        $this->method = $_SERVER['REQUEST_METHOD'];

	        # verb tunnelling
	        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
	            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
	                $this->method = 'DELETE';
	            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
	                $this->method = 'PUT';
	            } else {
	                throw new Exception("Unexpected Header");
	            }
	        }

	        switch($this->method) {
		        case 'DELETE':
		        default:
		        	$this->request = $this->_cleanInputs($_GET);
		            #$this->_response('Invalid Method', 405);
		            break;
	        }
	    }



	    
		public function processAPI() {
	        if (method_exists($this, $this->endpoint)) {
	        	# calls endpoint! :D
	        	try {
	            	return $this->_response($this->{$this->endpoint}($this->request));
	            } catch (APIException $e) {
	            	return $this->_response($e->getMessage(), $e->getCode());
	            }
	        }
	        return $this->_response("No Endpoint: $this->endpoint", 404);
	    }

	    private function _response($data, $status = 200) {
	        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
	        return json_encode($data);
	    }

	    private function _cleanInputs($data) {
	        $clean_input = Array();
	        if (is_array($data)) {
	            foreach ($data as $k => $v) {
	                $clean_input[$k] = $this->_cleanInputs($v);
	            }
	        } else {
	            $clean_input = trim(strip_tags($data));
	        }
	        return $clean_input;
	    }

	    private function _requestStatus($code) {
	        $status = array(  
	            200 => 'OK',
	            400 => 'Bad Request',
	            404 => 'Not Found',   
	            405 => 'Method Not Allowed',
	            500 => 'Internal Server Error',
	        ); 
	        return ($status[$code])?$status[$code]:$status[500]; 
	    }	    


		protected function _connectDB() {
			$dsn = 'mysql:dbname=' . $_SERVER['DB_DB'] . ';host=' . $_SERVER['DB_HOST'];
			try {
			    $this->db = new PDO($dsn, $_SERVER['DB_LOGIN'], $_SERVER['DB_PASSWD']);
			} catch (PDOException $e) {
			    return $this->_response($e->getMessage(), 500);
			}		
		}

		protected function _disconnectDB() {
			$this->db = Null;
		}		
	}