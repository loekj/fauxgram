<?php
# app/src/Controller/APIController.php
namespace Controller;
    use Silex\Application;
    use Symfony\Component\HttpFoundation\Request;
    use APIException\APIException as APIException;
    use Exception;
    use PDO;

    require_once 'APIHandler.php';
    class APIController extends APIHandler {
        //protected $User;

        public function __construct($request, $origin) {
            parent::__construct($request);
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


                $this->_connectDB();
                try {
                    $sql = $this->db->prepare("SELECT 1 FROM users WHERE email=?");
                    $sql->execute(array($this->request['email']));
                    if ($exists = $sql->fetchColumn()) {
                        throw new Exception("User with this email already exists!");
                    }
                } catch (Exception $e) {
                    throw new APIException($e->getMessage(), 400);
                }

                # Create a random salt and hash. Using Blowfish "$2a$"
                $hash = password_hash($this->request['password'],PASSWORD_BCRYPT, array('cost' => 10));

                $sql = "INSERT INTO users (email, password, fname, lname, roles) VALUES (" .
                    $this->db->quote($this->request['email']) . ", " .
                    $this->db->quote($hash) . ", " .
                    $this->db->quote($this->request['fname']) . ", " .
                    $this->db->quote($this->request['lname']) . ", " .
                    "'ROLE_USER')";
                try {
                    $sth = $this->db->query($sql);
                } catch(Exception $e) {
                    throw new APIException($e->getMessage(), 500);
                }

                try {
                    $sth = $this->db->query('SELECT * FROM users');
                    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                    return $rows;
                } catch(Exception $e) {
                    echo $e->getMessage();
                }

                $this->_disconnectDB();
                return "Registered successfully!";
            } elseif ($this->method == 'GET') {
                $required = array(
                    'email'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain email", 400);
                }
                $this->_connectDB();
                return "Succes!";

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
                $this->_connectDB();
                $this->_checkRegistered();

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
                $db_vals = array(
                    $this->request['email'],
                    $url_hash,
                    $this->request['url'],
                    $this->request['title'],
                    $ext,
                    $file_size,
                    $file_width,
                    $file_height
                    );
                $db_vals = array_map(array($this->db, 'quote'), $db_vals);
                $sql = "INSERT INTO images (owner, path, source, title, ext, bytes, width, height) VALUES (" . implode(', ', $db_vals) . ")";
                try {
                    $this->db->query($sql);                               
                } catch(Exception $e) {
                    $this->_rmImage($file_path);
                    throw new APIException($e->getMessage(), 500);
                }

                $this->_disconnectDB();
                return 'Success!';
            } elseif ($this->method == 'GET') {
                $required = array(
                    'id'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain id", 400);
                }
                $this->_connectDB();
                
                # Get image
                $sql = $this->db->prepare("SELECT * FROM images WHERE id=?");
                $sql->execute(array($this->request['id']));
                if (!$result = $sql->fetchAll(PDO::FETCH_ASSOC)) {
                    throw new APIException("No image found!", 400);
                }
                $this->_disconnectDB();
                $result = $result[0];
                unset($result['path']);
                return $result;             
            } elseif ($this->method == 'DELETE') {
                $required = array(
                    'email',
                    'password',
                    'id'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain email, password, id", 400);
                }
                $this->_connectDB();
                $this->_checkRegistered();
                
                # Get image path
                try {
                    $sql = $this->db->prepare("SELECT path FROM images WHERE id=?");
                    $sql->execute(array($this->request['id']));
                    if (!$result = $sql->fetch(PDO::FETCH_ASSOC)) {
                        throw new Exception("Image does not exist!");
                    }  
                } catch (Exception $e) {
                    throw new APIException($e->getMessage(), 400);
                }

                # Delete image from server
                $this->_rmImage(__DIR__ . '/../../resource/img/' . $result['path']);

                # Delete image from database
                try {
                    $sql = $this->db->prepare("DELETE FROM images WHERE id=?");
                    $sql->execute(array($this->request['id']));            
                } catch (Exception $e) {
                    throw new APIException($e->getMessage(), 400);
                }
                $this->_disconnectDB();
                return "Succes!";
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
                $this->_connectDB();
                $this->_checkRegistered();

                try {
                    $sql = $this->db->prepare("UPDATE images SET title=? WHERE id=?");
                    $sql->execute(array($this->request['title'], $this->request['id']));
                } catch (Exception $e) {
                    throw new APIException($e->getMessage(), 400);
                }
                if (!$sql->rowCount()) {
                    throw new APIException("Image ID does not exist or title had same value", 400);
                }
                $this->_disconnectDB();
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

                $this->_connectDB();
                $this->_checkRegistered();

                # Check if image exist
                $sql = $this->db->prepare("SELECT * FROM images WHERE id=?");
                $sql->execute(array($this->request['id']));
                if (!$result = $sql->fetch(PDO::FETCH_ASSOC)) {
                    throw new APIException("Image ID unknown.", 400);
                }

                # Check if content is good sized
                if (strlen($this->request['content']) > 255) {
                    throw new APIException("Comment is too long. Max 255 chars", 400);
                }

                $sql = "INSERT INTO comments (owner, content, imgid) VALUES (" .
                    $this->db->quote($this->request['email']) . ", " .
                    $this->db->quote($this->request['content']) . ", " .
                    $this->db->quote($this->request['id']) . ")"; 
                try {
                    $this->db->query($sql);                               
                } catch(Exception $e) {
                    throw new APIException($e->getMessage(), 500);
                }
                $this->_disconnectDB();
                return "Succes!";

            } elseif ($this->method == 'GET') {
                $required = array(
                    'id'
                    );

                if ($missing = array_diff_key(array_flip($required), $this->request)) {
                    throw new APIException("Invalid query string. Must contain image ID", 400);
                }
                $this->_connectDB();
                
                # Get image
                try {
                    $sql = $this->db->prepare("SELECT id, owner FROM images WHERE id=?");
                    $sql->execute(array($this->request['id']));
                    if (!$result_img = $sql->fetch(PDO::FETCH_ASSOC)) {
                        throw new Exception("Image ID unknown.");
                    }
                } catch (Exception $e) {
                    throw new APIException($e->getMessage(), 400);
                }
                
                # Get image comments
                try {
                    $sql = $this->db->prepare("SELECT id, owner, added, content FROM comments WHERE imgid=? ORDER BY added ASC");
                    $sql->execute(array($this->request['id']));
                    if (!$result_comments = $sql->fetchAll(PDO::FETCH_ASSOC)) {
                        throw new Exception("No comments yet for this image!");
                    }
                } catch (Exception $e) {
                    throw new APIException($e->getMessage(), 400);
                }
                $this->_disconnectDB();


                $result = array(
                    'image' => $result_img,
                    'comments' => $result_comments
                    );
                return $result;
            } else {
                throw new APIException("Endpoint only accepts GET and POST requests", 405);
            }
         }

     }
?>