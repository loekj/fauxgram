            # Check if user is registered
            $this->_connectDB();
            $sql = $this->db->prepare("SELECT * FROM users WHERE email=?");
            $sql->execute(array($this->request['email']));
            if (!$result = $sql->fetch()) {
                throw new APIException("User with this email is not registered!", 400);
            }

            # Check against hashed password in database
            if (!password_verify($this->request['password'], $result['password'])) {
                throw new APIException("Incorrect password!", 400);
            }
            