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

         protected function register() {
            if ($this->method != 'GET') {
                throw new APIException("Endpoint only accepts GET requests", 405);
            }
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
            $sql = $this->db->prepare("SELECT 1 FROM users WHERE email=?");
            $sql->execute(array($this->request['email']));
            if ($exists = $sql->fetchColumn()) {
                throw new APIException("User with this email already exists!", 400);
            }

            # Create a random salt and hash. Using Blowfish "$2a$"
            $hash = password_hash($this->request['password'],PASSWORD_BCRYPT, array('cost' => 10));


            try {
                $sql = "INSERT INTO users (email, password, fname, lname, roles) VALUES (" .
                    "'" . $this->request['email'] . "', " .
                    "'" . $hash . "', " .
                    "'" . $this->request['fname'] . "', " .
                    "'" . $this->request['lname'] . "', " .
                    "'ROLE_USER')";

                $sth = $this->db->query($sql);
            } catch(PDOException $e) {
                throw new APIException($e->getMessage(), 500);
            }

            try {
                $sth = $this->db->query('SELECT * FROM users');
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $rows;
            } catch(PDOException $e) {
                echo $e->getMessage();
            }

            $this->_disconnectDB();
            return "Registered successfully!";
         }

         protected function upload() {
            if ($this->method != 'POST') {
                return "Only accepts POST requests";
            }
            $required = array(
                'email',
                'password',
                'url',
                'title',
                );

            if ($missing = array_diff_key(array_flip($required), $this->request)) {
                throw new APIException("Invalid query string. Must contain email, password, url, title", 400);
            }
            # Check if user is registered
            $this->_connectDB();
            $sql = $this->db->prepare("SELECT 1 FROM users WHERE email=?");
            $sql->execute(array($this->request['email']));
            $result = $sql->fetch();
            echo json_encode($result);
            // if (!$exists = $sql->fetchColumn()) {
            //     throw new APIException("User with this email is not registered!", 400);
            // }

            $file = $this->request['url'];
            $file_headers = @get_headers($file);
            if($file_headers[0] == 'HTTP/1.1 404 Not Found') {
                throw new APIException("Image URL is invalid!", 400);
            }
            $url_split = explode('/', $this->request['url']);
            $url_head = array_pop($url_split);
            #echo $url_head;

            # Check file extension
            $valid_ext = array(
                'jpg',
                'png',
                'gif',
                'jpeg'
                );
            $head_split = explode('.', $url_head);
            $ext = strtolower($head_split[count($head_split)-1]);
            if (!array_key_exists($ext, array_flip($valid_ext))) {
                throw new APIException("Image URL extension is invalid!", 400);
            }
            # Hash the filename
            $url_head_hash = md5($url_head);
            try {
                $content = file_get_contents($this->request['url']);
                $fp = fopen(__DIR__ . '/../../resource/img/' . $url_head_hash, "w");
                fwrite($fp, $content);
                fclose($fp);
            } catch (Exception $e) {
                throw new APIException("Image URL content not copy-able!", 500);
            }


            // try {
            //     $sql = "INSERT INTO users (email, password, fname, lname, roles) VALUES (" .
            //         "'" . $this->request['email'] . "', " .
            //         "'" . $hash . "', " .
            //         "'" . $this->request['fname'] . "', " .
            //         "'" . $this->request['lname'] . "', " .
            //         "'ROLE_USER')";

            //     $sth = $this->db->query($sql);
            // } catch(PDOException $e) {
            //     throw new APIException($e->getMessage(), 500);
            // }

            $this->_disconnectDB();
            return '';
         }
     }
?>