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
	        return json_encode($data, JSON_UNESCAPED_SLASHES);
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
			} catch (Exception $e) {
			    return $this->_response($e->getMessage(), 500);
			}
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}

		protected function _disconnectDB() {
			$this->db = Null;
		}

		protected function _checkRegistered() {
            # Check if user is registered
            $sql = $this->db->prepare("SELECT * FROM users WHERE email=?");
            $sql->execute(array($this->request['email']));
            if (!$result = $sql->fetch(PDO::FETCH_ASSOC)) {
                throw new APIException("User with this email is not registered!", 400);
            }

            # Check against hashed password in database
            if (!password_verify($this->request['password'], $result['password'])) {
                throw new APIException("Incorrect password!", 400);
            }
        }

        protected function _rmImage($file_path) {
        	unlink($file_path);
        }

        protected function _createThumb($file_path, $ext, $dest, $size) {
        	switch($ext) {
        		case 'jpg':
        		case 'jpeg':
        			$source = imagecreatefromjpeg($file_path);
        			break;
        		case 'png':
        			$source = imagecreatefrompng($file_path);
        			break;
        		case 'gif':
        			$source = imagecreatefromgif($file_path);
        			break;
        	}
        	$curr_width = imagesx($source);
        	$curr_height = imagesy($source);

        	if ($curr_width > $size) {
        		$height = floor($curr_height * $size / $curr_width);
        		$width = $size;
        	} elseif ($curr_height > $size) {
        		$width = floor($curr_width * $size / $curr_height);
        		$height = $size;
        	} else {
        		$width = $size;
        		$height = $size;
        	}

        	$image_holder = imagecreatetruecolor($width, $height);

			imagecopyresampled($image_holder, $source, 0, 0, 0, 0, $width, $height, $curr_width, $curr_height);
			switch($ext) {
        		case 'jpg':
        		case 'jpeg':
        			imagejpeg($image_holder, $dest);
        			break;
        		case 'png':
        			imagepng($image_holder, $dest);
        			break;
        		case 'gif':
        			imagegif($image_holder, $dest);
        			break;
        	}
        	// chmod("$dest",777);
        }

        // protected function quote($x) {
        //     return $this->db->quote($x);
        // }        

	}