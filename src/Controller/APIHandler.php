<?php
// app/src/Controller/AdminController.php
namespace Controller;
	use APIException\APIException as APIException;
    use Symfony\Component\EventDispatcher\EventDispatcher;
	use APIEvent;
	use PDO;
	use Database;

	require_once __DIR__.'/../APIEvent/APIDispatcher.php';

	/*
	* Base class that handles the API request
	* Detects endpoint and calls corresponding method in child class
	*/
	abstract class APIHandler {
		protected $method = '';
		protected $endpoint = '';
		protected $db_obj = Null;
		protected $dispatcher = Null;
		protected $listener = Null;
		protected $db = Null;

		/*
		* Sets header, database object,
		* event dispatcher and listener, 
		* and request argument array
		*/
		public function __construct($request, Database\APIDatabase $db_obj) {
	        header("Access-Control-Allow-Orgin: *");
	        header("Access-Control-Allow-Methods: *");
	        header("Content-Type: application/json");
			
	        $this->db_obj = $db_obj;

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
	        		$this->request = $request;
	        		break;
		        default:
		            $this->_response('Invalid method', 405);
	        }

			$this->dispatcher = new EventDispatcher();
			$this->listener = new \APIEvent\APIListener();
			$this->dispatcher->addListener('new.comment', array($this->listener, 'onNewComment'));
	    }



	    /*
	    * Method gets called from web/api.php, calls endpoints function 
	    */ 
		public function processAPI() {
	        if (method_exists($this, $this->endpoint)) {
	        	try {
	            	return $this->_response($this->{$this->endpoint}($this->request));
	            } catch (APIException $e) {
	            	return $this->_response($e->getMessage(), $e->getCode());
	            }
	        }
	        return $this->_response("No Endpoint: $this->endpoint", 404);
	    }

	    /* 
	    * Encodes endpoint data to JSON with right header info
	    * Status code is wrapped in APIException
	    */
	    private function _response($data, $status = 200) {
	        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
	        return json_encode($data, JSON_UNESCAPED_SLASHES);
	    }

	    /*
	    * Gets status message from status code
	    */
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

	    /*
	    * Removes an image from the server
	    */
        protected function _rmImage($file_path) {
        	@unlink($file_path);
        }

        /*
        * Create thumbnail of image with maximum pixel size $size
        * Supports jpeg/jpg/gif/png
        */
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
        }
}