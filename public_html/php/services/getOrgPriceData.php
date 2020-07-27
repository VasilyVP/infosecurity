<?php # Сервис заполнения данных ЛК ЧОП

    use utilities\funcsLib;
    use exceptions\UtilsException, exceptions\SPDMException;
    use models\UserDataModel;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // проверяем авторизацию
    $auth = \engines\AuthenticationEngine::create();
    if (!$auth->isAuthenticated()) {
        $log->warning('Attempt to get data without authorization', ['METHOD' => __FILE__]);
        exit();
    }

    // что просят
    $get = $_GET['get'] ?? false;

    // ответ по умолчанию
    $response = [
        'code' => 0,
        'data' => 'empty'
    ];
    
    // получаем userID для вывода данных
    $userRole = $auth->getUserRole();
    if ($userRole == 'admin' || $userRole == 'moderator') {
        // если админ или модератор
        $userLogin = filter_var($_COOKIE['userLogin'] ?? false, FILTER_VALIDATE_EMAIL);

        
        $udm = UserDataModel::create();
        
        $userDataByEmail = $udm->getUserDataByEmail($userLogin);

        // проверяем, если перешли на страницу временных ЧОП(а ее бть не может и такого id_user нет)
        if (is_object($userDataByEmail)) {
            $userID = $userDataByEmail->id_user;
        } else {
            echo json_encode($response);
            exit();
        }        
    } else {
        // если user
        $userID = $auth->getUserID();
    }

    try {
        // подключаем модель работы с ЧОП
        $spdm = \models\ServiceProvidersDataModel::create();

        // если запрос Прейскуранта
        if ($get == 'price') {
            // формируем ответ
            $data = $spdm->getPriceDataByUser($userID);
            if (!($data->price ?? false)) throw new Exception("empty");

            $response = [
                'code' => 1,
                'data' => $data->price
            ];
        }
        // если запрос детальных данных
        if ($get == 'detail_data') {
            // формируем ответ
            $data = $spdm->getDetailDataByUser($userID);
            if (!($data->detail_data ?? false)) throw new Exception("empty");

            // раскодируем html сущности перед вставкой в ответ запроса
            $detail_data = funcsLib::htmlEntityArrDecode(json_decode($data->detail_data));

            $response = [
                'code' => 1,
                'data' => $detail_data,
                'chopFolder' => $data->id_client,
                'id' => $data->uid
            ];
        }
    } catch (SPDMException $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
        $response = [
            'code' => 0,
            'data' => 'Unexpected error'
        ];    
    } catch (Exception $e) {
        $response = [
            'code' => 2,
            'data' => 'no data'
        ];
    } finally {
        echo json_encode($response);
    }
