<?php ## Механизм аутентификации
    namespace engines;

    class AuthenticationEngine
    {
        static private $object;
        private $log;
        // RememberMeEngine
        private $rem;
        // признак аутентификации
        private $authenticated = false;
        private $userLogin = false;
        private $userID = false;
        private $userRole = false;
        // временная метка сессии
        private $time = false;

        # инициирует все входные переменные
        public function __construct()
        {
            // инициируем логи
            $this->log = \engines\LogEngine::create();

            // проверяем наличие rememberme и куку сессии
            $rememberMe = $_COOKIE['rememberme'] ?? false;
            $session = $_COOKIE[SESSION_NAME] ?? false;

            // если есть rememberMe
            if ($rememberMe) {
                // подключаем RememberMe engine и разбираем ее
                $rem = new \engines\RememberMeEngine();
                $rem->parseRememberMe();
                $this->rem = $rem;
            }

            // если есть сессионная кука стартуем сессию
            if ($session) {
                $this->startSession(); // true в параметр для read_close
                $this->loadSessionVars();
                
                // если аутентифицированы
                if ($this->authenticated) {
                    // если сессия протухла
                    if (time() - $this->time > SESSION_LIFE) {
                        // сбрасываем аутентификацию
                        $this->resetAuth();
                        // если есть rememberme - авторизуем по ней
                        if ($rememberMe) $this->authByRememberMe();
                    // если аутентификация актуальна и есть кука с корректной rememberme
                    } elseif ($rememberMe && $rem->isExist()) {
                        // и она не сегодняшняя - обновляем ее
                        if ( ($rem->getExpTime() - time()) < 60*60*24*89 ) {// *89 - дней
                            $rem->updateRememberMe($this->userID);
                        }                        
                    }
                }
            // если есть rememberme - стартуем сессию и авторизацию по ней
            } else {
                if ($rememberMe) $this->authByRememberMe();
            }
            //session_write_close();
        }

        # конструктор объекта в единственном экземпляре
        static public function create()
        {
            if (is_null(self::$object)) self::$object = new self();
            
            return self::$object;
        }

        # стартует сессию с заданными параметрами
        public function startSession($readAndClose = false)
        {
            // если сессия уже стартовала - сразу выходим
            if (session_status() === PHP_SESSION_ACTIVE) return true;
            
            // устанавливаем параметры куки для сессий
            $params = [
                'save_path' => SESSION_SAVE_PATH,
                'name' => SESSION_NAME,
                'use_strict_mode' => 1,
                'cookie_lifetime' => SESSION_LIFE,
                'cookie_secure' => COOKIE_SECURE, // поменять для продакшн в конфигах
                'cookie_httponly' => true,
                'read_and_close' => $readAndClose
            ];

            // стартуем сессию с параметрами
            if (!$status = session_start($params)) $this->log->Error("Can't start session", ['METHOD' => __METHOD__]);

            return $status;
        }

        # перезапускает сессию и аутентификацию по rememberme
        private function authByRememberMe()
        {
            $rem = $this->rem;
            // если rememberme корректная
            if ($rem->isExist()) {
                // и если она валидная и обновилась
                if ($rem->checkUpdateRememberMe()) {
                    // запускаем и обновляем сессию
                    $this->startSession();
                    
                    // сохраняем параметры в сессию
                    $this->saveSessionAuthVars([
                        'login' => $rem->getLogin(),
                        'userID' => $rem->getUserID(),
                        'userRole' => $rem->getUserRole(),
                        'authenticated' => true
                    ]);

                    $this->loadSessionVars();
                } else {
                    $this->logout();
                    sleep(1);
                }
            }
        }

        # сохраняет переданные переменные в сессию + временную метку
        public function saveSessionAuthVars($arr = [])
        {   // сохраняем все переменные в сессию
            foreach($arr as $var => $value) {
                $_SESSION['auth'][$var] = $value;
            }
            // сохраняем временную метку
            $_SESSION['auth']['time'] = time();
        }

        # заполняет переменные engine из сессии
        private function loadSessionVars()
        {
            $this->authenticated = $_SESSION['auth']['authenticated'] ?? false;
            $this->userLogin = $_SESSION['auth']['login'] ?? false;
            $this->userID = $_SESSION['auth']['userID'] ?? false;
            $this->userRole = $_SESSION['auth']['userRole'] ?? false;
            $this->time = $_SESSION['auth']['time'] ?? false;
        }

        # удаляет сессию и авторизацию с rememberme
        public function logout()
        {
            // удаляем сессионную куку и сессию
            $this->killSession();

            // удаляем rememberme куку
            setcookie('rememberme', '', time()-10000, '/', $_SERVER['HTTP_HOST']);
            unset($_COOKIE['rememberme']);

            //s$this->log->debug($_COOKIE['userLogin']);

            // удаляем userLogin куку
            //setcookie('userLogin', '', time()-10000, '/', $_SERVER['HTTP_HOST']);
            //unset($_COOKIE['userLogin']);

            //$this->authenticated = false;
            $this->loadSessionVars();
        }

        # сбрасывает аутентификацию
        public function resetAuth()
        {
            $this->killSession();
            $this->loadSessionVars();
        }

        // удаляет сессионную куку и сессию
        public function killSession()
        {
            $_SESSION = [];
            setcookie(session_name(), '', time()-10000);
            unset($_COOKIE[session_name()]);
            if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
        }

        # возвращает логин пользователя сессии
        public function getUserLogin()
        {
            return $this->userLogin;
        }

        # возвращает статус аутентификации
        public function isAuthenticated()
        {
            return $this->authenticated;
        }

        # возвращает id_user из БД
        public function getUserID()
        {
            return $this->userID;
        }

        # возвращает роль пользователя
        public function getUserRole()
        {
            return $this->userRole;
        }

        /*
        # создает JWT токен для remember_me
        public function createJWT($login)
        {
            $JWT_header = [
                'alg' => 'HS256',
                'typ' => 'JWT'
            ];

            $JWT_payload = [
                'iss' => 'scanox.pro',
                //'exp' => time() + 3600,
                'login' => $login,
                'userID' => $userID,
                'randomString' => bin2hex(random_bytes(15))
            ];

            $header = base64_encode(json_encode($JWT_header));
            $payload = base64_encode(json_encode($JWT_payload));

            $JWT_body = $header . '.' . $payload;

            $signature = hash_hmac('sha256', $JWT_body, $GLOBALS['secretKeyJWT']);
            $JWT = $JWT_body . '.' . $signature;
            
            return $JWT;
        }

        # проверяет валидность JWT токена, возвращает объект вида {check: boolean, login: string}
        public function checkJWT($JWT)
        {
            $JWT_parts = explode('.', $JWT);

            $answer = new class {
                public $check = false;
            };

            if (count($JWT_parts) != 3) return $answer;

            try {
                $payload = json_decode(base64_decode($JWT_parts[1]));

                $check = hash_hmac('sha256', $JWT_parts[0] . '.' . $JWT_parts[1], $secret_key);

                if ($check === $JWT_parts[2]) { 
                    $answer->check = true;
                    $answer->login = $payload->login;
                }                
            } catch (Exception $e) {
                $this->log->error('JWT check error', ['METHOD' => __FILE__]);
            } finally {
                return $answer;
            }
            
        }
        */

        
    }
