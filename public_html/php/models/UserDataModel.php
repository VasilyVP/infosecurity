<?php # класс модели работы со справочниками пользователей и ролей
    namespace models;

    use \exceptions\MDMException;

    class UserDataModel
    {
        private $db;
        private $log;
        static private $object;

        private function __construct()
        {
            $this->log = \engines\LogEngine::create();

            try {
                $connect = \engines\DBConnection::create();
                $this->db = $connect->getConnection();
            } catch (\exceptions\DBException $e) {
                // если не подключилось - выбрасываем исключение
                throw new \exceptions\MDMException("Can't initiate correct UserDataModel due Db problems");
            }            
        }

        ## конструктор объекта в единственном экземпляре
        public static function create()
        {
            if (is_null(self::$object)) self::$object = new self();
            return self::$object;
        }

        # возвращает список справочников (массив)
        public function getRolesList()
        {
            $query = "SELECT id_role, name FROM roles";
            try {
                $stm = $this->db->query($query);
                // возвращаем массив значений
                return $stm->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        # генератор возвращает список пользователей по их роли
        public function getUsersByRole($role)
        {
            $query = "SELECT active, users.name AS name, surname, email, roles.name AS role 
                        FROM users JOIN roles USING(id_role) WHERE roles.name = :role";
            try {
                $stm = $this->db->prepare($query);
                $stm->execute([':role' => $role]);
                while ($rowObj = $stm->fetchObject()) yield $rowObj;
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        # возвращает список пользователей по строке запроса
        public function getUsersByQuery($queryString, $limit = 5)
        {
            $query = "SELECT active, users.name AS name, surname, email, roles.name AS role
                        FROM users JOIN roles USING(id_role) 
                        WHERE CONCAT(users.name, ' ', surname) LIKE :queryStr
                        OR email LIKE :queryStr LIMIT :limit";

            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':queryStr', "%$queryString%", \PDO::PARAM_STR);
                $stm->bindValue(':limit', $limit, \PDO::PARAM_INT);
                $stm->execute();

                return $stm->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        # обновляет статус и роль пользователя
        public function setUserRoleStatus($inputs)
        {
            $query = "UPDATE users SET active = :active, id_role = :role WHERE email = :email";
            try {
                $stm = $this->db->prepare($query);
                $stm->execute([
                    ':active' => $inputs['active'],
                    ':role' => $inputs['role'],
                    ':email' => $inputs['email']
                ]);
                // возвращаем статус
                if ($stm->rowCount() == 0) return false;
                else return true;
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        # устанавливает новый пароль пользователя
        public function setUserPassword($login, $hash)
        {
            $query = "UPDATE users SET password = :hash, remember_token = null WHERE email = :login";
            try {
                $stm = $this->db->prepare($query);
                $stm->execute([
                    ':hash' => $hash,
                    ':login' => $login,
                ]);
                // возвращаем статус
                if ($stm->rowCount() == 0) return false;
                else return true;
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new \exceptions\MDMException("PDO error in the " . __METHOD__);
            }
        }

        # обновляем пользовательские данные
        public function updateUserData($params)
        {
            // формируем запросы в зависимости от меняем ли пароль
            $withPassw = array_key_exists('hash', $params);
            if ($withPassw) {
                $query = "UPDATE users SET name = :name, surname = :surname, patronymic = :patronymic,
                            phone = :phone, email = :email, password = :password, remember_token = null WHERE id_user = :userID";
            } else {
                $query = "UPDATE users SET name = :name, surname = :surname, patronymic = :patronymic,
                            phone = :phone, email = :email WHERE id_user = :userID";
            }
            
            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':name', $params['name']);
                $stm->bindValue(':surname', $params['surname']);
                $stm->bindValue(':patronymic', $params['patronymic']);
                $stm->bindValue(':phone', $params['phone']);
                $stm->bindValue(':email', $params['email']);
                $stm->bindValue(':userID', $params['userID']);
                // если с паролем
                if ($withPassw) $stm->bindValue(':password', $params['hash']);

                $stm->execute();
                
                // если изменений нет
                if ($stm->rowCount() == 0) throw new MDMException('Zero rows updated', 300);
                // если изменения есть
                else return true;
            } catch (\PDOException $e) {
                // если email уже зарегистрирован такой
                if ($e->getCode() == 23000) {
                    throw new \exceptions\MDMException("This email allready exist", 23000);
                }
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new \exceptions\MDMException("PDO error in the " . __METHOD__);
            }
        }

        # добавляет нового пользователя
        public function addNewUser($params)
        {
            $query = "INSERT INTO users VALUES (
                null, 0, :name, :surname, :patronymic, null, :email, :password, null, :roleID, DEFAULT)";

            // ответ по умолчанию
            $response = 'no';
            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':name', $params['name']);
                $stm->bindValue(':surname', $params['surname']);
                $stm->bindValue(':patronymic', $params['patronymic']);
                $stm->bindValue(':email', $params['email']);
                $stm->bindValue(':password', $params['password']);
                $stm->bindValue(':roleID', $params['roleID'], \PDO::PARAM_INT);
                $stm->execute();

                if ($stm->rowCount() !== 1) {
                    $this->log->error("Can't insert new user", ['METHOD' => __METHOD__]);
                } else {
                    $response = 'ok';
                }
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    $response = 'double';
                } else {
                    $this->log->error('SQL request error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                }
            } finally {
                return $response;
            }
        }

        # удаляем последнего добавленного пользователя
        public function delNewUser()
        {
            $query = "DELETE FROM users WHERE id_user = :lastUserID";
            try {
                $lastUserID = $this->db->lastInsertId();
                $stm = $this->db->prepare($query);
                $stm->execute([':lastUserID' => $lastUserID]);
                return true;
            } catch (\PDOException $e) {
                $this->log->error('SQL error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        # возвращает данные пользователя по userID
        public function getUserDataByUserID($userID)
        {
            $query = "SELECT users.name AS name, surname, patronymic, phone, email, remember_token, roles.name AS role 
                        FROM users JOIN roles USING (id_role) WHERE id_user = :userID";
            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':userID', $userID, \PDO::PARAM_INT);
                $stm->execute();

                return $stm->fetchObject();
            } catch (\PDOException $e) {
                // при ошибке пишем в лог и выбрасываем исключение
                $this->log->error('SQL request error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new \exceptions\MDMException('SQL query error');
            }
        }

        # возвращает данные пользователя по email
        public function getUserDataByEmail($email)
        {
            $query = "SELECT id_user, active, users.name AS name, surname, patronymic, password, roles.name AS role 
                FROM users JOIN roles USING (id_role) WHERE email = :email";

            try {
                $stm = $this->db->prepare($query);
                $stm->execute([':email' => $email]);

                //return ($obj = $stm->fetchObject()) ? $obj : false;
                return $stm->fetchObject();

            } catch (\PDOException $e) {
                // при ошибке пишем в лог и выбрасываем исключение
                $this->log->error('SQL request error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new \exceptions\MDMException('SQL query error');
            }
        }

        # возвращает hash пароля пользователя
        public function getUserPasswHash($userID)
        {
            $query = "SELECT password FROM users WHERE id_user = :userID";
            try {
                $stm = $this->db->prepare($query);
                $stm->execute([':userID' => $userID]);

                return $stm->fetchObject()->password;
            } catch (\PDOException $e) {
                // при ошибке пишем в лог и выбрасываем исключение
                $this->log->error('SQL request error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new \exceptions\MDMException('SQL query error');
            }
        }

        # обновляет rememberme в базе
        public function setRememberMe($token, $userID)
        {
            $query = "UPDATE users SET remember_token = :token WHERE id_user = :userID";
            try {
                $stm = $this->db->prepare($query);
                $stm->execute([
                    ':token' => $token,
                    ':userID' => $userID
                    ]
                );
                if ($stm->rowCount() == 1) return true;
                else return false;
            } catch (\PDOException $e) {
                $this->log->error("Can't update remember_me token: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        # активирует пользователя
        public function activateUser($email)
        {
            $query = "UPDATE users SET active = 1 WHERE email = :email";
            try {
                $stm = $this->db->prepare($query);
                $stm->execute([':email' => $email]);

                if ($stm->rowCount() === 1) return true;
                else return false;
            } catch (\PDOException $e) {
                // при ошибке пишем в лог и выбрасываем исключение
                $this->log->error("Can't change active status for user $email: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new \exceptions\MDMException('SQL query error');
            }
        }

    }
