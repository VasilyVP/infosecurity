<?php # компонент проверки и заполнения на странице сброса пароля

    namespace components;

    class resetPasswCheckComponent {

        private $login = false;
        private $check = false;
        private $errorMessage;

        public function __construct()
        {
            $log= \engines\LogEngine::create();

            $code = $_GET['code'] ?? false;
            // проверяем code на наличие
            if (!$code) return;
            
            // разбираем код
            $codeArr = explode('.', $code);
            // проверяем токен
            if (count($codeArr) !== 3) return;
            
            $login = base64_decode($codeArr[0]);

            // проверяем контрольный код и время ссылки (30 минут)
            if ($codeArr[2] === hash_hmac('sha256', $codeArr[1] . $login, SECRET_MAIL_KEY)) {
                if (time() - $codeArr[1] < 60 * 30) {
                    // стартуем сессию без аутентификации
                    $auth = \engines\AuthenticationEngine::create();
                    $auth->startSession();

                    // сохраняем логин
                    $auth->saveSessionAuthVars(['login' => $login]);

                    // сохраняем статус в check и логин
                    $this->check = true;
                    $this->login = $login;
                } else {
                    $this->errorMessage = 'Ссылка просрочена';
                }
            } else {
                $log->warning('Incorrect password reinstatment link', ['METHOD' => __METHOD__]);
                $this->errorMessage = 'Ссылка некорректна';
                // берем паузу
                sleep(1);
            }
        }

        public function getCheck()
        {
            return $this->check;
        }

        public function getLogin()
        {
            return $this->login;
        }

        public function getErrorMessage()
        {
            return $this->errorMessage;
        }
    }
