<?php ## Сервис формирования списка городов ЧОП по строке запроса
    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    $response = [
        'code' => 0,
        'data' => false,
        'message' => 'Unexpected error'
    ];

    $auth = \engines\AuthenticationEngine::create();
    // проверяем авторизацию и роль пользователя (admin || moderator)
    if (!($auth->isAuthenticated() && ($auth->getUserRole() === 'admin' || $auth->getUserRole() === 'moderator'))) {
        $response['message'] = 'Unauthorized access';
        echo json_encode($response);
        exit();
    }

    // фильтруем входные параметры
    $args = [
        'query' => [ 'filter' => FILTER_SANITIZE_STRING ],
        'status' => [ 'filter' => FILTER_SANITIZE_STRING ],
    ];
    $inputs = filter_input_array(INPUT_GET, $args);

    // если есть несоответствующее значение - выходим
    if (in_array(false, $inputs, true)) {
        $log->warning('Incorrect inputs', ['METHOD' => __FILE__]);
        exit();
    }

    try {
        // подключаем модель cdm
        $adm = \models\AddressesDataModel::create();

        $cities = $adm->getCitiesByQuery($inputs['query'], $inputs['status'], 5);

        $response = [
            'code' => 1,
            'data' => $cities,
            'message' => 'Ok'
        ];
    // обработка пока ни на что не влияет!!!
    } catch (Exception $e) {
        
    } finally {
        echo json_encode($response);
    }
