<?php # Компонент активации пользовательского экаунта/логина

    namespace components;

    use engines\LogEngine, engines\MailgunEngine;
    use models\UserDataModel;
    use exceptions\MailgunEngineException;

    class ActivateUserAccount
    {
        private $log;
        private $userStatus;
        private $requestedLogin;

        /** При создании объекта сразу активируем email */
        public function __construct()
        {
            $this->log = LogEngine::create();

            $this->requestedLogin = filter_input(INPUT_GET, 'login', FILTER_VALIDATE_EMAIL);

            // если есть email
            if ($this->requestedLogin) {
                try {
                    $udm = UserDataModel::create();
    
                    // активируем логин и возвращаем статус
                    if ($udm->activateUser($this->requestedLogin)) {
                        $this->userStatus = 'activated';

                        // подписываем пользователя к рассылке
                        $mge = new MailgunEngine;
                        $mge->updateUserAtMailingList(USERS_MAILINGLIST, $this->requestedLogin, ['subscribed' => true]);

                    } else $this->userStatus = 'empty';
        
                } catch (MailgunEngineException $e) {
                    $this->log->error("Can't subscribe {$this->requestedLogin} at Registered Users mailing list", ['METHID' => __FILE__]);
                } catch (Exception $e) {
                    $this->userStatus = 'error';
                }
            } else $this->userStatus = 'error';            
        }

        public function getStatus()
        {
            return $this->userStatus;
        }

        public function getLogin()
        {
            return $this->requestedLogin;
        }

    }
