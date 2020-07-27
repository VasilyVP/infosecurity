<?php ## Сервис регистрации пользователей
    // импортируем пространство имен MailgunEngine
    use engines\MailgunEngine;
    use engines\CheckRobots;
    use exceptions\MailgunEngineException;
    use utilities\funcsLib;
    
    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // проверяем reCAPTCHA
    $token = $_POST['captcha_token'] ?? false;
    // статус, что робот
    $status = [
        'status' => 'Warning',
        'code' => 2,
        'message' => 'You are robot'
    ];
    // если токена нет отправляем ответ и выходим
    if (!$token) {
        $log->warning("There's no reCAPTCHA token", ['METHOD' => __FILE__]);
        // отправляем ответ и выходим
        echo json_encode($status);
        exit();
    }
    
    // проверяем токен
    $cap = new CheckRobots();
    $check = $cap->getCheckByCaptcha('registration', $token);
    // если робот - логируем, шлем ответ и выходим
    if ($check->status == 'robot') {
        $log->warning("Registration attempt by robot detected. Score: $check->score", ['METHOD' => __METHOD__]);
        // отправляем ответ и выходим
        echo json_encode($status);
        exit();
    }

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
        'user_email' => [ 'filter' => FILTER_VALIDATE_EMAIL ]
    ];
    // фильтр
    $inputs = filter_input_array(INPUT_POST, $args);

    // если пустой - выходим
    if (!is_array($inputs)) {
        $log->warning('Incorrect inputs, EMPTY', ['METHOD' => __FILE__]);
        exit();
    }

    // если есть несоответствующее значение - выходим
    if (in_array(false, $inputs, true)) {
        $log->warning('Incorrect inputs', ['METHOD' => __FILE__]);
        exit();
    }
    
    $email = $inputs['user_email'];
    $name = $inputs['user_name'];
    $surname = $inputs['user_surname'];
    $patronymic = $inputs['user_patronymic'];

    // проверяем и хэшируем пароль
    /*
    $password = $_POST['user_password'] ?? false;
    if (!$password || (mb_strlen($password) > 20)) exit();
    $password = password_hash($password, PASSWORD_DEFAULT);
    */
    
    // формируем ответ по умолчанию(ошибка)
    $status = [
        'status' => 'Error',
        'code' => 0,
        'message' => 'Unexpected error'
    ];

    $password = funcsLib::generatePassword(10);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // подключаем модель работы со справочниками
        $mdm = \models\UserDataModel::create();

         // добавляем пользователя в БД
        $result = $mdm->addNewUser([
            'name' => $name, 
            'surname' => $surname, 
            'patronymic' => $patronymic,
            'email' => $email,
            'password' => $passwordHash,
            'roleID' => 3 ]
        );
    // если ошибка
    } catch (Exception $e) {
        // отправляем ответ и выходим
        echo json_encode($status);
        exit();
    }   
    // если данные пришли
    if ($result == 'ok') {
        // формируем код для ссылки письма 
        $time = time();
        $code = base64_encode($email) . '.';
        $code .= $time . '.';
        $code .= hash_hmac('md5', $time . $email, SECRET_MAIL_KEY);
        
        $thisSiteLink = 'https://' . $_SERVER['SERVER_NAME'];
        $logoLink = $thisSiteLink . '/imgs/logos/scanox_logo.png';
        $link = $thisSiteLink . '/confirm_email/?code=' . $code;
        $user_name = $name . ($patronymic ? ' ' . $patronymic : '');
        
        // отправляем письмо подтверждения email
        try {
            $mge = new MailgunEngine;
            $result = $mge->sendMail(
                [
                    'from' => ['address' => 'registration', 'name' => EMAIL_SENDER_NAME],
                    'to' => [ $email => "$name $surname"],
                    'subject' => 'Подтверждение регистрации на scanox.pro',
                    'o:tag' => ['registrations_tag'],
                    'o:tracking-opens' => 'yes',
                    'o:tracking-clicks' => 'yes',
                ],
                [
                    //'MESSAGE' => $msg,
                    'LOGOTYPE' => $logoLink,
                    'USER_NAME' => $user_name,
                    'LOGIN' => $email,
                    'PASSWORD' => $password,
                    'CONFIRM_EMAIL_LINK' => $link,
                    'THIS_SITE_LINK' => $thisSiteLink
                ],
                'tpl_confirm_registration.html'
            );
        } catch (MailgunEngineException $e) {
            $log->warning("Can't send registration email: " . $e->getMessage(), ['METHOD' => __METHOD__]);
        }

        // если письмо ушло
        if ($result->http_response_code == 200) {
            // добавляем пользователя в список рассылки в статусе unsubscribe
            try {  
                $result = $mge->addUserToMailingList(USERS_MAILINGLIST, [
                    'address' => $email,
                    'name' => "$name $surname",
                    'subscribed' => 'no',
                    'vars' => json_encode([
                        'name' => $name,
                        'surname' => $surname,
                        'patronymic' => $patronymic,
                        'unsubscribe_token' => $mge->getUnsubscribeToken($email)
                    ])
                ]);
            } catch (MailgunEngineException $e) {
                $log->error("Can't add $email to registered users mailing list: " . $e->getMessage());
            }
            // формируем ответ с подтверждением
            $status = [
                'status' => 'Ok',
                'code' => 1,
                'message' => 'One user added'
            ];
        } else {
            $log->error("Can't send confirmation email", ['METHOD' => __FILE__]);
            // удаляем вставленную запись, если на хостинге
            if (HOSTING) $result = $mdm->delNewUser();
        }

    } elseif ($result == 'double') {
        $status = [
            'status' => 'Error',
            'code' => 1062,
            'message' => 'Dublicate e-mail'
        ];
    }
    // отправляем ответ
    echo json_encode($status);
