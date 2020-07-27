<?php # проверяет запрос на восстановление пароля по email со страницы логина и если Ok - шлет email 
      # со ссылкой на страницу восстановления
    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // проверяем входные параметры и если не Ок, то выходим
    $args = [
        'login_email' => [ 'filter' => FILTER_VALIDATE_EMAIL ]
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

    $login = $inputs['login_email'];

    // формируем ответ по умолчанию(ошибка)
    $status = [
        'status' => 'Error',
        'code' => 0,
        'message' => 'Unexpected error'
    ];

    try {
        $udm = \models\UserDataModel::create();
        $userObj = $udm->getUserDataByEmail($login);
    } catch (Exception $e) {
        // в случае ошибок отправляем ответ об ошибке и выходим
        echo json_encode($status);
        exit();
    }

    // если есть строка с результатом
    if ($userObj) {
        // читаем статус
        $active = $userObj->active;
        $name = $userObj->name;
        $surname = $userObj->surname;
        $patronymic = $userObj->patronymic;
                
        // если пользователь найден и статус активен - шлем письмо восстановления пароля и формируем ответ
        if ($active) {                    
            // формируем код для ссылки письма 
            $time = time();
            $code = base64_encode($login) . '.';
            $code .= $time . '.';
            $code .= hash_hmac('sha256', $time . $login, SECRET_MAIL_KEY);
            // ссылка и сообщение
            $thisSiteLink = 'https://' . $_SERVER['HTTP_HOST'];
            $logoLink = $thisSiteLink . '/imgs/logos/scanox_logo.png';
            $link = $thisSiteLink . '/reset_password/?code=' . $code;
            $user_name = $name . ($patronymic ? ' ' . $patronymic : '');
                    
            // подключаем почтовый модуль
            $mail = new \engines\MailEngine();
            // шлем письмо подтверждения на почту
            $mail->sendToByPHPMailer(
                [
                    'from' => 'robot',
                    'fromName' => 'Scanox',
                    'to' => [ $login => $name . ' ' . $surname ],
                    'subject' => 'Восстановление пароля на scanox.pro',
                    'template' => 'tpl_change_password.html'
                ],
                [ 
                    //'MESSAGE' => $msg
                    'LOGOTYPE' => $logoLink,
                    'USER_NAME' => $user_name,
                    'CHANGE_PASSWORD_LINK' => $link
                ]
            );
            // если письмо ушло
            if ($mail->getStatus()) {
                // формируем ответ
                $status = [
                    'status' => 'Ok',
                    'code' => 1,
                    'message' => 'Login Ok'
                ];
            }
        // если пользователь неактивен - формируем ответ и ждем секунду
        } else {
            $status = [
                'status' => 'No',
                'code' => 2,
                'message' => 'Unregistered user'
            ];
            sleep(1);
        }                
    } elseif ($userObj === false) {
        // если email не найден формируем ответ и ждем секунду
        $status = [
            'status' => 'No',
            'code' => 2,
            'message' => "Unregistered user"
        ];
        sleep(1);
    }
    
    // отправляем ответ
    echo json_encode($status);
