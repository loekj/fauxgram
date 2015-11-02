<?php 
namespace Database;

    class APIDatabase {
        private $db = Null;

        public function __construct() {
            #$this->_connectDB();
        }
        public function __destruct() {
            $this->db = Null;
        }                

        private function _connectDB() {
            $dsn = 'mysql:dbname=' . $_SERVER['DB_DB'] . ';host=' . $_SERVER['DB_HOST'];
            $this->db = new PDO($dsn, $_SERVER['DB_LOGIN'], $_SERVER['DB_PASSWD']);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        public function checkRegistered() {
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
        public function SELECT($from_array, $where_array, $select_array = "*", $except_msg = "") {
            if (is_array($select_array)) {
                $select_array = implode(", ", $select_array);
            }
            $from_array = implode(", ", $from_array);
            $where_array = implode(", ", $where_array);
            try {
                $sql = $this->db->prepare("SELECT " . $select_array . " FROM " . $from_array . " WHERE " . $where_array);
                $sql->execute();
                if (!$result = $sql->fetchAll()) {
                    throw new Exception($except_msg);
                }
            } catch (Exception $e) {
                throw new APIException($e->getMessage(), 400);
            }
            return $result;
        }      
    }
?>