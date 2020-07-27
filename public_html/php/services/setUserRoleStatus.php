<?php ## Сервис обновления Роли и Статуса пользователя
    use \engines\MailgunEngine;
    use \exceptions\MailgunEngineException;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    $auth = \engines\AuthenticationEngine::create();
    // проверяем авторизацию и роль пользователя
    if (!($auth->isAuthenticated() && $auth->getUserRole() === 'admin')) {
        $log->warning('trying to receive users list without authorization and role', ['METHOD' => __FILE__]);
        exit();
    }

    // проверяем входные параметры и если не Ок, то выходим
    $args = [
        'email' => [ 'filter' => FILTER_VALIDATE_EMAIL ],
        'role' => [ 'filter' => FILTER_VALIDATE_INT ],
        'active' => [ 'filter' => FILTER_VALIDATE_INT ]
    ];
    // фильтр
    $inputs = filter_input_array(INPUT_POST, $args);

    // если есть несоответствующее значение - выходим
    if (in_array(false, $inputs, true)) {
        $log->warning('Incorrect inputs', ['METHOD' => __FILE__]);
        exit();
    }
    // устанавливаем статус ответа по умолчанию
    $status = [
        'status' => 'No',
        'code' => 0,
        'message' => 'Error'
    ];

    try {
        // подключаем модель
        $udm = \models\UserDataModel::create();

        // сохраняем статус и роль и формируем ответ
        if ($udm->setUserRoleStatus($inputs)) {
            // обновляем статус подписки пользователя subscribed/unsubscribed
            $subscribed = $inputs['active'] ? true : false;
            $mge = new MailgunEngine;
            $mge->updateUserAtMailingList(USERS_MAILINGLIST, $inputs['email'], ['subscribed' => $subscribed]);

            $status = (object)[
                'status' => 'Ok',
                'code' => 1,
                'message' => 'User updated'
            ];
        }
    } catch (MailgunEngineException $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
        $status['code'] = 2;
        $status['message'] = 'Only unsubscription error in MailgunEngine';
    } catch (Exception $e) {
        // не инициализировался UDM
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
    } finally {
        // возвращаем ответ
        echo json_encode($status);
    }
