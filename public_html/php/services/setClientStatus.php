<?php ## Сервис изменения статуса страницы чоп (активна/неактивна)
    use \engines\MailgunEngine;
    use \exceptions\MailgunEngineException;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    $response = [
        'status' => 'Error',
        'code' => 0,
        'message' => 'Unexpected error'
    ];

    $auth = \engines\AuthenticationEngine::create();
    // проверяем авторизацию и роль пользователя (admin || moderator)
    if (!($auth->isAuthenticated() && ($auth->getUserRole() === 'admin' || $auth->getUserRole() === 'moderator'))) {
        $log->warning('trying to receive clients list without authorization and role', ['METHOD' => __FILE__]);
        echo json_encode($response);
        exit();
    }

    // фильтруем входные параметры    
    $args = [
        'clientID' => [ 'filter' => FILTER_VALIDATE_INT ],
        'active' => [ 'filter' => FILTER_VALIDATE_INT ],
        'status' => [ 'filter' => FILTER_SANITIZE_STRING ],
        'email' => [ 'filter' => FILTER_VALIDATE_EMAIL ]
    ];
    // фильтр
    $inputs = filter_input_array(INPUT_POST, $args);

    // если есть несоответствующее значение - выходим
    if (in_array(false, $inputs, true)) {
        $log->warning('Incorrect inputs', ['METHOD' => __FILE__]);
        
        $response['message'] = 'Incorrect inputs';
        echo json_encode($response);
        exit();
    }    

    try {
        // подключаем модель cdm
        $cdm = \models\ClientsDataModel::create();
        // обновляем статус ЧОП
        if ($cdm->setClientStatus($inputs['clientID'], $inputs['status'], $inputs['active'])) {
            
            // временные ЧОП удаляем(меняем статус) также из списка рассылки temp_clients
            if ($inputs['status'] == 'temp') {
                $listAddress = TEMPORARY_CLIENTS_MAILINGLIST;
                //else $listAddress = USERS_MAILINGLIST;
                $subscribed = $inputs['active'] ? true : false;

                $mge = new MailgunEngine;
                // обновляем статус подписки subscribed/unsubscribed
                $mge->updateUserAtMailingList($listAddress, $inputs['email'], ['subscribed' => $subscribed]);
            }
            $response = [
                'status' => 'Ok',
                'code' => 1,
                'message' => 'Data updated'
            ];
        }
    } catch (MailgunEngineException $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
        $response['message'] = 'Only unsubscription error';
    } catch (Exception $e) {
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
    } finally {
        echo json_encode($response);
    }
