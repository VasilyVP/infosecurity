<?php # компонент подтверждения email

    namespace components;

    class confirmEmailComponent
    {
        private $status;
        private $email;

        public function __construct()
        {
            $log = \engines\LogEngine::create();
            
            $code = $_GET['code'] ?? false;
            
            if (!$code) return;
        
            $codeArr = explode('.', $code);
            // проверяем число элементов токена
            if (count($codeArr) !== 3) return;

            $email = base64_decode($codeArr[0]);

            $this->email = $email;
        
            if ($codeArr[2] == hash_hmac('md5', $codeArr[1] . $email, SECRET_MAIL_KEY)) {
                // проверяем валидность по времени - 24 часа
                if (time() - $codeArr[1] < 60*60*24) {
                    try {
                        $udm = \models\UserDataModel::create();
                        // если сохранили - формируем сообщение
                        if ($udm->activateUser($email)) {
                            $this->email = $email;
                            // Email подтвержден
                            $this->status = 1;
                            // активируем подписку на рассылки
                            $this->subscribeUser();
                        } else {
                            //$log->error("Can't update active field", ['METHOD' => __METHOD__]);
                            // Такой email уже подтвержден либо не зарегистрирован
                            $this->status = 2;
                        }
                    } catch (\Exception $e) {
                        $log->error("Can't update active field in db: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                        // Что-то пошло не так, попробуйте позже
                        $this->status = 0;
                    }
                } else {
                    // Ссылка просрочена
                    $this->status = 3;
                }
            }
        }

        public function getStatus()
        {
            return $this->status;
        }

        public function getEmail()
        {
            return $this->email;
        }

        // активирует подписку пользователю в списке рассылки
        private function subscribeUser()
        {
            if (!$this->email) return false;
            try {
                $mg = new \engines\MailgunEngine();

                $mg->updateUserAtMailingList(USERS_MAILINGLIST, $this->email, ['subscribed' => 'yes']);
            } catch (\exceptions\MailgunEngineException $e) {
                $log->error("Can't activate subscription for $email in " . USERS_MAILINGLIST, ['METHOD' => __METHOD__]);
                return false;
            }
            return true;
        }
    }
