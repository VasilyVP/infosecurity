<?php # компонент заполнения полей ЛК
    namespace components;

    class PADataFillComponent
    {
        private $log;
        private $userData;

        public function __construct()
        {
            // подключаем подуль логирования
            $this->log = \engines\LogEngine::create();

            // подключаем модуль аутентификации
            $auth = \engines\AuthenticationEngine::create();

            $userID = $auth->getUserID();

            try {
                // подключаем модель
                $udm = \models\UserDataModel::create();

                // если есть строка с результатом
                if ($obj = $udm->getUserDataByUserID($userID)) {
                    $this->userData['userName'] = $obj->name;
                    $this->userData['userSurname'] = $obj->surname;
                    $this->userData['userPatronymic'] = $obj->patronymic;
                    $this->userData['userPhone'] = $obj->phone;
                    $this->userData['userEmail'] = $obj->email;
                } else {
                    $this->log->warning("Don't find logged user in the users", ['METHOD' => __METHOD__]);
                }
            } catch (\Exception $e) {
                // при ошибке ничего не делаем :(
            }
        }

        public function getUserData($field)
        {
            return $this->userData[$field] ?? '';
        }
    }
