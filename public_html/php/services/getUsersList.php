<?php ## Сервис формирования списка пользователей по ролям или ФИО/email
    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    $response = [
        'code' => 0,
        'data' => false,
        'message' => 'No data or error'
    ];

    $auth = \engines\AuthenticationEngine::create();
    // проверяем авторизацию и роль пользователя
    if (!($auth->isAuthenticated() && $auth->getUserRole() === 'admin')) {
        echo json_encode($response);
        exit();
    }

    // получаем роль из запроса и текст запроса, если есть
    $askRole = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_STRING);// ?? false;
    $queryString = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);// ?? false;
    
    try {
        // подключаем модель mdm
        $mdm = \models\UserDataModel::create();
        // получаем список ролей
        $rolesListObj = $mdm->getRolesList();        

        // запоминаем список ролей в ответе
        $data['rolesList'] = $rolesListObj;
    
        // если ищем по роли, то возвращаем список пользователей
        $users = [];
        if ($askRole) {
            foreach($mdm->getUsersByRole($askRole) as $user) $users[] = $user;
        // если ищем по поисковой строке
        } elseif ($queryString) {
            // проверяем поисковую строку
            if (preg_match('/^[a-zа-яё -]{1,10}$/iu', $queryString) || filter_input(INPUT_GET, 'query', FILTER_VALIDATE_EMAIL)) {
                $users = $mdm->getUsersByQuery($queryString, 6);
            }
        }
        $data['users'] = count($users) > 0 ? $users : [];
        
        $response = [
            'code' => 1,
            'data' => $data,
            'message' => 'Ok'
        ];
    } catch (Exception $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
    } finally {
        echo json_encode($response);
    }
