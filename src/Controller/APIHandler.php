<?php
// app/src/Controller/AdminController.php
namespace Controller;
	use APIException\APIException as APIException;
    use Symfony\Component\EventDispatcher\EventDispatcher;
    use Symfony\Component\EventDispatcher\Event;	
	use APIEvent;
	use PDO;
	use Database;

	require_once __DIR__.'/../APIEvent/APIDispatcher.php';
	require_once __DIR__.'/../APIEvent/EmailEvent.php';
	# cannot have an instance
	abstract class APIHandler {
		protected $method = '';
		protected $endpoint = '';
		protected $db_obj = Null;
		protected $dispatcher = Null;
		protected $listener = Null;
		protected $db = Null;

		public function __construct($request, Database\APIDatabase $db_obj) {
	        header("Access-Control-Allow-Orgin: *");
	        header("Access-Control-Allow-Methods: *");
	        header("Content-Type: application/json");
			
	        $this->db_obj = $db_obj;
	        $this->_connectDB();

	        $this->endpoint = array_shift($request);
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
	        	case 'PUT':
	        	case 'POST':
	        	case 'GET':
	        	case 'DELETE':
	        		$this->request = $this->_escapeInputs($request);
	        		break;
		        default:
		            $this->_response('Invalid method', 405);
	        }
			$this->dispatcher = new EventDispatcher();
			$this->listener = new \APIEvent\APIListener();
			$this->dispatcher->addListener('new.comment', array($this->listener, 'onNewComment'));
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

	    private function _escapeInputs($data) {
			$data = array_map(array($this->db, 'quote'), $data);
	    	return $data;
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

		private function _connectDB() {
            $dsn = 'mysql:dbname=' . $_SERVER['DB_DB'] . ';host=' . $_SERVER['DB_HOST'];
            $this->db = new PDO($dsn, $_SERVER['DB_LOGIN'], $_SERVER['DB_PASSWD']);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

		public function disconnectDB() {
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
}