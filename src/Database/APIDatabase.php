<?php 
namespace Database;
    use APIException\APIException as APIException;
    use Exception;
    use PDO;

    class APIDatabase {
        private $db = Null;

        public function __construct() {
            $this->_connectDB();
        }
        public function __destruct() {
            $this->db = Null;
        }                

        /*
        * Connet to the database using PDO
        */
        private function _connectDB() {
            $dsn = 'mysql:dbname=' . $_SERVER['DB_DB'] . ';host=' . $_SERVER['DB_HOST'];
            $this->db = new PDO($dsn, $_SERVER['DB_LOGIN'], $_SERVER['DB_PASSWD']);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        /*
        * Checks whether user exist and if password is correct
        */
        public function checkRegistered($email, $password) {
            # Check if user is registered
            $query = "SELECT * FROM users WHERE email=?";
            $value_array = array($email);
            $result = $this->CustomQuery(
                $query,
                $value_array,
                "User with this email is not registered!"
                );
            $result = $result[0];

            if (!password_verify($password, $result['password'])) {
                throw new APIException("Incorrect password!", 400);
            }
        }

        /*
        * Checks whether an entry exist meeting certain conditions
        * and throws APIException with certain message
        */
        public function NotExists($from_array, $where_array = array(), $except_msg = "", $oper = "=") {
            $from_array = implode(", ", $from_array);
            
            if ($where_array) {
                $where_keys = array_keys($where_array);
                $where_clause = " WHERE " . implode(" AND ", array_map(function ($x) use ($oper) {return $x . $oper . "?";} ,$where_keys)); 
            } else {
                $where_clause = "";
            }

            try {
                $sql = $this->db->prepare("SELECT * FROM " . $from_array . $where_clause);
                $sql->execute(array_values($where_array));
                if ($result = $sql->fetchAll(PDO::FETCH_ASSOC)) {
                    throw new Exception($except_msg);
                }
            } catch (Exception $e) {
                throw new APIException($e->getMessage(), 400);
            }
            return $result;
        }     

        /* 
        * Insert query template  
        */
        public function Insert($table, $insert_array) {
            $insert_keys = implode(", ", array_keys($insert_array));
            $insert_placeholders = implode(", ", array_map(function () {return "?";}, $insert_array));
            try {
                $sql = $this->db->prepare("INSERT INTO " . $table . "(" . $insert_keys . ") VALUES (" . $insert_placeholders . ")");
                $sql->execute(array_values($insert_array));
            } catch (Exception $e) {
                throw new APIException($e->getMessage(), 400);
            }
        }

        /* 
        * Custom query template mostly for intricate join queries
        */
        public function CustomQuery($query, $value_array, $except_msg = "", $no_fetch = FALSE, $no_throw = FALSE) {
            try {
                $sql = $this->db->prepare($query);
                $sql->execute($value_array);
                if (!$no_fetch) {
                    if (!$result = $sql->fetchAll(PDO::FETCH_ASSOC)) {
                        if (!$no_throw) {
                            throw new Exception($except_msg);
                        }
                    }
                } else {
                    if (!$result = $sql->rowCount()) {
                        throw new Exception($except_msg);
                    };
                }
            } catch (Exception $e) {
                throw new APIException($e->getMessage(), 400);
            }
            
            return $result;
        }
                           
    }
?>