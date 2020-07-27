<?php ## Сервис замены пароля пользователя при его сбросе
    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // подключаем БД
    /*
    require_once DB_CONNECT;
    $db = $GLOBALS['db_connect'] ?? false;
*/
    //$mail = new \engines\MailEngine();
    $auth = \engines\AuthenticationEngine::create();

    $auth->startSession();

    // формируем ответ по умолчанию(ошибка)
    $status = [
        'status' => 'Error',
        'code' => 0,
        'message' => 'Unexpected error'
    ];

    // читаем логин из сессии
    $login = $_SESSION['auth']['login'] ?? false;
    
    // обнуляем логин и сессии
    $auth->killSession();

    // если логина нет - выходим
    if (!$login) {
        $log->warning('No login in the session', ['METHOD' => __FILE__]);
        echo json_encode($status);
        exit();
    }

    $password = $_POST['user_password'] ?? false;
    if (!$password || (mb_strlen($password) > 20)) {
        $log->warning('Incorrect password in the inputs', ['METHOD' => __FILE__ ]);
        echo json_encode($status);
        exit();
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $mdm = \models\UserDataModel::create();

        if ($mdm->setUserPassword($login, $hash)) {
            $status = [
                'status' => 'Ok',
                'code' => 1,
                'message' => 'Data updated'
            ];
        } else {
            $status = [
                'status' => 'No',
                'code' => 2,
                'message' => "The data hasn't been updated"
            ];
        }
    } catch (Exception $e) {
        //$log->error('PDO Error by user password update: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
    } finally {
        // отправляем ответ
        echo json_encode($status);
    }
