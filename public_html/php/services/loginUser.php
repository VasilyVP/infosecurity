<?php ## Сервис проверки логина пользователей
    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';
    require_once TEMPLATES_CFG;

    // подключаем NoRobot
    //$noRobot = new \engines\NoRobot('general');
   
    // проверяем входные параметры и если не Ок, то выходим
    $args = [
        'login_email' => [ 'filter' => FILTER_VALIDATE_EMAIL ],
        'remember_me' => ['filter' => FILTER_VALIDATE_BOOLEAN ]
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
    // формируем ответ по умолчанию - ошибка
    $status = [
        'status' => 'Error',
        'code' => 0,
        'message' => 'Unexpected error'
    ];

    // проверяем входной пароль на корректность, если большой - выходим
    $password = $_POST['login_password'] ?? false;
    if (!$password || (mb_strlen($password) > 20)) exit();

    try {
        $mdm = \models\UserDataModel::create();
        // получаем данные пользователя по email
        $obj = $mdm->getUserDataByEmail($inputs['login_email']);
    } catch (Exception $e) {
         // в случае ошибок отправляем ответ об ошибке и выходим
         echo json_encode($status);
         exit();
    }
   
    if ($obj) {
        // читаем хэш пароля и id_user
        $hash = $obj->password;
        $userID = $obj->id_user;
        $active = $obj->active;
        $role = $obj->role;
                
        // проверяем пароль и статус активности
        if (password_verify($password, $hash) && $active) {
            // если Ok, формируем ответ
            $status = [
                'status' => 'Ok',
                'code' => 1,
                'message' => 'Password Ok',
                'rout' => HOME_BY_ROLES[$role]
            ];
                    
            // запускаем аутентификацию
            $auth = \engines\AuthenticationEngine::create();
                    
            // инициируем сессию
            $auth->startSession();

            // сохраняем логин и обновляем статус в сессии
            $auth->saveSessionAuthVars([
                'login' => $inputs['login_email'],
                'userID' => $userID,
                'authenticated' => true,
                'userRole' => $role
            ]);

            // если стоит remember_me
            $rememberMe = $inputs['remember_me'] ?? false;
            if ($rememberMe) {
                // подключаем RememberMeEngine
                $rem = new \engines\RememberMeEngine();
                        
                // формируем токен авторизации (по умолчанию 90 дней), возвращает объект {token, exp}
                $remember = $rem->createRememberToken($userID);

                // обновляем куку и токен в БД
                $rem->saveRememberMe($remember, $userID);
            }
        // если пароль неверен: формируем ответ и ждем секунду
        } else {
            $status = [
                'status' => 'No',
                'code' => 3,
                'message' => 'Invalid password'
            ];
            sleep(1);
        }
    // если нет строки с результатом (email не найден)
    } elseif ($obj === false) {
        $status = [
            'status' => 'No',
            'code' => 2,
            'message' => "Unregistered user"
        ];
    }
    
    // отправляем ответ
    echo json_encode($status);
