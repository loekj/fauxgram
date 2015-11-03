<?php
# app/src/Controller/APIController.php
namespace Controller;
    use APIException\APIException as APIException;
    use Exception;
    use PDO;

    require_once 'APIHandler.php';
    class APIController extends APIHandler {
        //protected $User;

        public function __construct($request, $origin, $db_obj) {
            parent::__construct($request, $db_obj);
        }

         protected function user() {
            if ($this->method == 'POST') {
                $required = array(
                    'email',
                    'password',
                    'fname',
                    'lname'
                    );
                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain email, password, fname, lname", 400);
                }

                $from_array = array("users");
                $where_array = array("email" => $this->request['email']);
                $this->db_obj->NotExists(
                    $from_array,
                    $where_array,
                    "User with this email already exists!"
                    );
                
                # Create a random salt and hash. Using Blowfish "$2a$"
                $hash = password_hash($this->request['password'],PASSWORD_BCRYPT, array('cost' => 10));
                $insert_array = array(
                    'email' => $this->request['email'],
                    'password' => $hash,
                    'fname' => $this->request['fname'],
                    'lname' => $this->request['lname']
                    );
                $this->db_obj->Insert("users", $insert_array);

                return "Registered successfully!";

            } elseif ($this->method == 'GET') {
                $required = array(
                    'email'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain email", 400);
                }
                
                # Get image and comments
                $thumbpath = __DIR__ . '/../../resource/img/thumb/';
                $query = "SELECT a.id AS userid, a.email, CONCAT(a.fname, ' ', a.lname)" .
                        " AS name, CONCAT('" . $thumbpath . "', b.path) AS thumbpath". 
                        " FROM users a JOIN images b on a.email=b.owner" .
                        " WHERE a.email=?";
                $value_array = array($this->request['email']);
                $result = $this->db_obj->CustomQuery($query, $value_array, "User unknown or no images uploaded yet.");
                
                return $result;
            } else {
                throw new APIException("Endpoint only accepts GET requests", 405);
            }
         }



         protected function img() {
            if ($this->method == 'PUT') {
                $required = array(
                    'email',
                    'password',
                    'url',
                    'title',
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain email, password, url, title", 400);
                }
                $this->db_obj->checkRegistered($this->request['email'], $this->request['password']);

                $file = $this->request['url'];
                $file_headers = @get_headers($file);
                if (!$file_headers) {
                    throw new APIException("Image URL is invalid or no header found!", 400);
                }  
                if (strpos($file_headers[0], '200') == FALSE) {
                    throw new APIException("Image URL is invalid!", 400);
                }

                $file_size = 0;
                foreach($file_headers as $key => $value) {
                    preg_match('/Content-Length:\s*(\d+)/', $value, $matches);
                    if ($matches) {
                        $file_size = intval($matches[1]);
                        continue;
                    }
                    unset($matches);
                    preg_match('/Content-Type:\s*(.+)/', $value, $matches);
                    if ($matches) {
                        preg_match('/image\/(.+)/', $matches[1], $match_image);
                        if (!$match_image) {
                            throw new APIException("Image URL is not an image!", 400);
                        } else {
                            $ext = $match_image[1];
                        }
                    }     
                    unset($matches);           
                }

                # max 6.7 mb
                if ($file_size > pow(2,26)) {
                    throw new APIException("Image is too big to copy!", 400);
                }


                # Check file extension valid
                $valid_ext = array(
                    'jpg',
                    'png',
                    'gif',
                    'jpeg'
                    );

                # Extract extension if not found in header
                if (!isset($ext)) {
                    $url_split = explode('/', $this->request['url']);
                    $url_head = array_pop($url_split);                
                    $head_split = explode('.', $url_head);
                    $ext = strtolower($head_split[count($head_split)-1]);
                }

                if (!array_key_exists($ext, array_flip($valid_ext))) {
                    throw new APIException("Image type is invalid. Must be jpg, jpeg, gif or png.", 400);
                }

                # Hash the filename
                $url_hash = md5($this->request['url']);
                $file_path = __DIR__ . '/../../resource/img/' . $url_hash;

                # Check if already exist
                if (file_exists($file_path)) {
                    throw new APIException("Image already exists!", 500);
                }

                # Copy the file
                try {
                    $content = file_get_contents($this->request['url']);
                    $fp = fopen($file_path, "w");
                    fwrite($fp, $content);
                    fclose($fp);
                    // chmod("$file_path",777);
                } catch (Exception $e) {
                    throw new APIException("Image URL content not copy-able!", 500);
                }


                # Get image dimensions
                if (!$file_dim = getimagesize($file_path)) {
                    $this->_rmImage($file_path);
                    throw new APIException("Image content unreadable!", 500);
                }
                $file_width = $file_dim[0];
                $file_height = $file_dim[1];

                # Create thumbnail of 200 by 200
                $dest = explode('/',$file_path);
                array_splice($dest, count($dest)-1, 0 ,"thumb");
                $dest = implode('/',$dest);
                try {
                    $this->_createThumb($file_path, $ext, $dest, 200);
                } catch (Exception $e) {
                    $this->_rmImage($file_path);
                    throw new APIException("Thumbnail could not be created!", 500);
                }
                if (!file_exists($dest)) {
                    $this->_rmImage($file_path);
                    throw new APIException("Thumbnail could not be created!", 500);                
                }

                # Insert image info into database
                $insert_array = array(
                    'owner' => $this->request['email'],
                    'path' => $url_hash,
                    'source' => $this->request['url'],
                    'title' => $this->request['title'],
                    'ext' => $ext,
                    'bytes' => $file_size,
                    'width' => $file_width,
                    'height' => $file_height
                    );

                try {
                    $this->db_obj->Insert("images", $insert_array);
                } catch(APIException $e) {
                    $this->_rmImage($file_path);
                    throw $e;
                }
                
                return 'Success!';
            } elseif ($this->method == 'GET') {
                $required = array(
                    'id'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain id", 400);
                }
                

                $query = "SELECT * FROM images WHERE id=?";
                $value_array = array($this->request['id']);
                $result = $this->db_obj->CustomQuery(
                    $query,
                    $value_array,
                    "No image found!"
                    );
                return $result[0];       

            } elseif ($this->method == 'DELETE') {
                $required = array(
                    'email',
                    'password',
                    'id'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain email, password, id", 400);
                }

                $this->db_obj->checkRegistered($this->request['email'], $this->request['password']);
                
                # Get image path
                $query = "SELECT path FROM images WHERE id=? AND owner=?";
                $value_array = array($this->request['id'], $this->request['email']);
                $result = $this->db_obj->CustomQuery(
                    $query,
                    $value_array,
                    "No image found!"
                    );
                $result = $result[0];

                $file_path = __DIR__ . '/../../resource/img/' . $result['path'];
                $thumb_path = __DIR__ . '/../../resource/img/thumb/' . $result['path'];
                
                $this->_rmImage($thumb_path);
                if (file_exists($file_path)) {
                    $this->_rmImage($file_path);
                }
                if (file_exists($thumb_path)) {
                    $this->_rmImage($thumb_path);
                }

                $query = "DELETE a,b FROM images a LEFT OUTER JOIN comments b ON a.id=b.imgid WHERE a.id=?";
                $value_array = array($this->request['id']);
                $result = $this->db_obj->CustomQuery($query, $value_array, "", TRUE);

                return $result ? "Succes!" : "Image does not exist";

            } elseif ($this->method == 'POST') {
                $required = array(
                    'email',
                    'password',
                    'title',
                    'id'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain email, password, title, id", 400);
                }

                $this->db_obj->checkRegistered($this->request['email'], $this->request['password']);

                $query = "UPDATE images SET title=? WHERE id=? AND owner=?";
                $value_array = array(
                    $this->request['title'],
                    $this->request['id'],
                    $this->request['email']
                    );
                $result = $this->db_obj->CustomQuery(
                    $query, 
                    $value_array, 
                    "Image ID does not exist or title had same value", 
                    TRUE
                    );

                return "Succes!";
            } else {
                throw new APIException("Endpoint only accepts GET, POST, PUT and DELETE requests", 405);
            }
         }

         protected function comment() {

            if ($this->method == 'POST') {
                $required = array(
                    'email',
                    'password',
                    'content',
                    'id'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain email, password, content, id", 400);
                }

                $this->db_obj->checkRegistered($this->request['email'], $this->request['password']);

                $query = "SELECT owner FROM images WHERE id=?";
                $value_array = array($this->request['id']);
                $result = $this->db_obj->CustomQuery(
                    $query, 
                    $value_array, 
                    "Image ID unknown."
                    );

                $result_owner = $result[0];

                # Check if content is good sized
                if (strlen($this->request['content']) > 255) {
                    throw new APIException("Comment is too long. Max 255 chars", 400);
                }

                # Get all emails
                $query = "SELECT DISTINCT owner FROM comments WHERE imgid=?";
                $value_array = array($this->request['id']);
                $result = $this->db_obj->CustomQuery(
                    $query,
                    $value_array, 
                    "",
                    FALSE,
                    TRUE
                    );

                $insert_array = array(
                    'owner' => $this->request['email'],
                    'content' => $this->request['content'],
                    'imgid' => $this->request['id']
                    );
                $this->db_obj->Insert("comments", $insert_array);

                $email_array = array();
                foreach($result as $idx => $arr) {
                    foreach($arr as $k => $v) {
                        if ($v != $result_owner['owner'] && $v != $this->request['email']) {
                            array_push($email_array, $v);
                        }
                    }
                }
                $email_event_obj = new \APIEvent\EmailEvent($result_owner['owner'], $this->request['email'], $email_array, $this->request['content'], $this->request['id']);
                $this->dispatcher->dispatch('new.comment', $email_event_obj);
                return "Succes!";

            } elseif ($this->method == 'GET') {
                $required = array(
                    'id'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain image ID", 400);
                }

                # Get image and comments
                $thumbpath = __DIR__ . '/../../resource/img/thumb/';
                $query =    "SELECT a.id AS imgid, " . 
                            "a.owner AS imageowner," .
                            " a.path AS imagepath, b.id AS commentid," .
                            " b.owner AS commentowner, b.added, b.content AS comment" .
                            " FROM images a JOIN comments b on a.id=b.imgid" .
                            " WHERE a.id=?" .
                            " ORDER BY b.added ASC";
                $value_array = array($this->request['id']);
                $result = $this->db_obj->CustomQuery(
                            $query, 
                            $value_array, 
                            "Image ID unknown or no comments for this image."
                            );

                return $result;

            } else {
                throw new APIException("Endpoint only accepts GET and POST requests", 405);
            }
         }

     }
?>