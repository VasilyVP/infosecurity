<?php ## механизм обработки задач RememberMe
    namespace engines;

    class RememberMeEngine
    {
        private $exist = false;
        private $expTime = false;
        private $userID = false;
        private $login = false;
        private $userRole = false;
        private $token = false;
        //private $db;
        private $udm;
        private $log;

        public function __construct()
        {
            // подключаем СУБД
            /*
            $this->db = $GLOBALS['db_connect'] ?? false;
            */
            // подключаем логирование
            $this->log = \engines\LogEngine::create();
            // подключаем модель UserDataModel
            try {
                $this->udm = \models\UserDataModel::create();
            } catch (\exceptions\MDMException $e) {
                $this->log->error("Can't initiate UDM: " . $e->getMessage(), ['METHOD' => __METHOD__]);
            }
        }

        # парсит куку rememberme
        public function parseRememberMe()
        {
            $rememberMe = $_COOKIE['rememberme'] ?? false;
            
            if ($rememberMe) {
                try {
                    // декодируем токен из куки и проверяем типы
                    $result = json_decode(base64_decode($rememberMe));
                    if (is_object($result) && isset($result->exp, $result->userID, $result->token)) {
                        $this->expTime = $result->exp;
                        $this->userID = $result->userID;
                        $this->token = $result->token;
                        $this->exist = true;
                    } else {
                        $this->exist = false;
                    }
                } catch (\Exception $e) {
                    $this->log->warning("Error by rememberMe decoding", ['METHOD' => __METHOD__]);
                } catch (\Error $er) {
                    $this->log->warning("Rememberme token has been broken: " . $er->getMessage(), ['METHOD' => __METHOD__]);
                }
            }
        }

        # формирует rememberme токен авторизации по user ID - объект вида {token, exp}        
        public function createRememberToken($userID, $days = 90)
        {
            // формируем токен авторизации
            $exp = time() + 60*60*24 * $days;
            $token = bin2hex(random_bytes(20));
            $remember = [
                'exp' => $exp,
                'userID' => $userID,
                'token' => $token
            ];
            
            $result = new class {};
            $result->token = base64_encode(json_encode($remember));
            $result->exp = $exp;
            
            return $result;
        }

        # сохраняем rememberme куку и токен в БД
        public function saveRememberMe($remember, $userID)
        {    
            // сохраняем rememberme токен и если ок - формируем куку
            if ($this->udm && $this->udm->setRememberMe($remember->token, $userID)) {
                setcookie('rememberme', $remember->token, $remember->exp, '/', $_SERVER['HTTP_HOST'], COOKIE_SECURE, true);
            }
        }

        # обновляем rememberme куку и токен в БД
        public function updateRememberMe($userID)
        {
            // формируем токен авторизации
            $remember = $this->createRememberToken($userID);

            $userID = (int)$userID;
            // сохраняем токен в БД и куку
            $this->saveRememberMe($remember, $userID);        
        }

        # проверяем токен из куки и если Ok - обновляем его в куках и базе
        public function checkUpdateRememberMe()
        {
            $userID = (int)$this->userID;           
            try {
                // если есть строка с результатом
                if ($obj = $this->udm->getUserDataByUserID($userID)) {
                    // разбираем токен
                    $rememberToken = $obj->remember_token;
                            
                    // если токена в базе нет - выходим
                    if (!$rememberToken) return false;
                            
                    $remember = json_decode(base64_decode($rememberToken));
                    $token = $remember->token;
                            
                    // если токены куки и в базе совпадают и токен в базе не протух
                    if ( ($token == $this->token) && ($remember->exp > time()) ) {
                        $this->login = $obj->email;
                        $this->userRole = $obj->role;
                        // обновляем токены и куки
                        $this->updateRememberMe($userID);
                        // возвращаем успех проверки
                        return true;
                        // если токены не совпадают - возвращаем false
                    } else return false;
                } else return false;    
            } catch (\Exception $e) {
                $this->log->error("Can't read remember_token: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        public function isExist()
        {
            return $this->exist;
        }

        public function getExpTime()
        {
            return $this->expTime;
        }

        public function getUserID()
        {
            return $this->userID;
        }

        public function getLogin()
        {
            return $this->login;
        }

        public function getUserRole()
        {
            return $this->userRole;
        }

    }
