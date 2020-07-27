<?php ## Сервис формирования списка клиентов по названию/email/телефон
    use utilities\funcsLib;

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
        'city' => [ 'filter' => FILTER_SANITIZE_STRING ],
        'useCity' => [ 'filter' => FILTER_SANITIZE_STRING ]
    ];
    $inputs = filter_input_array(INPUT_GET, $args);

    // если есть несоответствующее значение - выходим
    if (in_array(false, $inputs, true)) {
        $log->warning('Incorrect inputs', ['METHOD' => __FILE__]);
        exit();
    }

    try {
        // подключаем модель cdm
        $cdm = \models\ClientsDataModel::create();

        $clients = $cdm->getClientsByQuery($inputs['query'], $inputs['status'], $inputs['city'], $inputs['useCity'], 10);

        // раскодируем html сущности перед вставкой в ответ запроса
        $clients = funcsLib::htmlEntityArrDecode($clients);

        $response = [
            'code' => 1,
            'data' => $clients,
            'message' => 'Ok'
        ];
    // обработка пока ни на что не влияет!!!
    } catch (Exception $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
    } catch (ErrorException $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
    } finally {
        echo json_encode($response);
    }
