<?php ## Сервис обновления данных пользователя
    
    use engines\MailgunEngine;
    use exceptions\MailgunEngineException;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // подключаем модуль аутентификации
    $auth = \engines\AuthenticationEngine::create();

    if (!$auth->isAuthenticated()) {
        $log->warning('Non authorized user update attemtion', ['METHOD' => __FILE__]);
        exit();
    }

    // формируем ответ по умолчанию(ошибка)
    $status = [
        'status' => 'Error',
        'code' => 0,
        'message' => 'Unexpected error'
    ];

    // проверяем входные параметры и если не Ок, то выходим
    $args = [
        'user_name' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => [ 'regexp' => '/^[a-zа-яё -]{2,20}$/iu' ]
        ],
        'user_surname' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => [ 'regexp' => '/^[a-zа-яё -]{2,20}$/iu' ]
        ],
        'user_patronymic' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => [ 'regexp' => '/^[a-zа-яё -]{0,20}$/iu' ]
        ],
        'user_phone' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^[0-9\-()+ ]{0,20}$/' ]
        ],
        'user_email' => [ 'filter' => FILTER_VALIDATE_EMAIL ],
        'edit-password' => ['filter' => FILTER_VALIDATE_BOOLEAN ]
    ];
    // фильтр
    $inputs = filter_input_array(INPUT_POST, $args);

    // если пустой - выходим
    if (!is_array($inputs)) exit();

    // если есть несоответствующее значение - выходим
    if (in_array(false, $inputs, true)) {
        $log->warning('Incorrect inputs', ['METHOD' => __FILE__]);
        exit();
    }

    // проверяем старый пароль на размер
    $passwordOld = $_POST['user_confirm_password'] ?? false;
    if (!$passwordOld || (mb_strlen($passwordOld) > 20)) {
        $log->warning('Incorrect inputs in old password', ['METHOD' => __FILE__]);
        exit();
    }

    try {
        $udm = \models\UserDataModel::create();
        
        $userID = $auth->getUserID();

        $hash = $udm->getUserPasswHash($userID);
        
        // проверяем пароль
        if (!password_verify($passwordOld, $hash)) {
            // если не Ok, формируем ответ и выходим
            $status = [
                'status' => 'No',
                'code' => 2,
                'message' => 'Password mismatch'
            ];
        } else { // если все ОК
            // проверяем: есть ли новый пароль (признак)
            $editPassword = $_POST['edit-password'] ?? false;
            // параметры запроса (без пароля)
            $params = [
                'name' => $inputs['user_name'],
                'surname' => $inputs['user_surname'],
                'patronymic' => $inputs['user_patronymic'],
                'phone' => $inputs['user_phone'],
                'email' => $inputs['user_email'],
                'userID' => $userID
            ];

            // если есть новый пароль
            if ($editPassword) {
                $passwordNew = $_POST['user_password1'] ?? false;

                //проверяем новый пароль и если он больше 20 символов - выходим
                if (!$passwordNew || (mb_strlen($passwordNew) > 20)) {
                    $log->warning('Incorrect inputs in new password', ['METHOD' => __FILE__]);
                    exit();
                }

                // хэшируем пароль
                $hash = password_hash($passwordNew, PASSWORD_DEFAULT);

                // добавляем пароль в запрос
                $params['hash'] = $hash;
            }

            // проверяем менялся ли email
            $oldEmail = $udm->getUserDataByUserID($userID)->email;
            if ($inputs['user_email'] != $oldEmail) $emailUpdated = true;
            else $emailUpdated = false;

            // обновляем данные
            if ($udm->updateUserData($params)) {
                $status = [
                    'status' => 'Ok',
                    'code' => 1,
                    'message' => 'Data updated'
                ];
            }

            // если надо - обновляем подписки
            if ($emailUpdated) {
                try {
                    $mge = new MailgunEngine;
                    // отписываем старый
                    $result = $mge->updateUserAtMailingList(USERS_MAILINGLIST, $oldEmail, ['subscribed' => false]);
                    // подписываем новый
                    $result = $mge->addUserToMailingList(USERS_MAILINGLIST, [
                        'address' => $inputs['user_email'],
                        'name' => "{$inputs['user_name']} {$inputs['user_surname']}",
                        'subscribed' => 'yes',
                        'upsert' => 'yes',
                        'vars' => json_encode([
                            'name' => $inputs['user_name'],
                            'surname' => $inputs['user_surname'],
                            'patronymic' => $inputs['user_patronymic'],
                            'unsubscribe_token' => $mge->getUnsubscribeToken($inputs['user_email'])
                        ]),
                    ]);
                } catch (MailgunEngineException $e) {
                    $log->error("Can't resubscribe $oldEmail to {$inputs['user_email']}: " . $e->getMessage(), ['METHOD' => __FILE__]);
                }
            }            
        }
    } catch (\exceptions\MDMException $e) {
        // если email уже зарегистрирован такой
        if ($e->getCode() == 23000) {
            $status = [
                'status' => 'Error',
                'code' => 1062,
                'message' => 'Dublicate e-mail'
            ];
        } elseif ($e->getCode() == 300) {
            $status = [
                'status' => 'Ok',
                'code' => 300,
                'message' => $e->getMessage()
            ];
        }
    } finally {
        // отправляем ответ
        echo json_encode($status);
    }
