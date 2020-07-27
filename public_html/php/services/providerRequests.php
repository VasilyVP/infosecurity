<?php ## Сервис работы с запросами на публикацию страниц ЧОП
    use models\ServiceProvidersDataModel;
    use engines\MailgunEngine;
    use utilities\funcsLib;
    use exceptions\SPDMException, exceptions\MailgunEngineException;

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
        'request' => [ 'filter' => FILTER_SANITIZE_STRING ],
        'id' => [ 'filter' => FILTER_SANITIZE_STRING ],
        'name' => [ 'filter' => FILTER_SANITIZE_STRING ],
        'email' => [ 'filter' => FILTER_VALIDATE_EMAIL ],
        'orgName' => [ 'filter' => FILTER_SANITIZE_STRING ],
        'comment' => [ 'filter' => FILTER_SANITIZE_STRING ]
    ];
    $inputs = filter_input_array(INPUT_GET, $args);
    
    try {
        // подключаем модель работы с ЧОП
        $spdm = ServiceProvidersDataModel::create();

        if ($inputs['request'] == 'list') {
            // получаем запросы
            $limit = 1;
            $data = $spdm->getProviderRequests($limit);

            // раскодируем html сущности перед вставкой в ответ запроса
            $data['requests'] = funcsLib::htmlEntityArrDecode($data['requests']);
                
            $response = [
                'code' => 1,
                'data' => $data,
                'message' => 'Ok'
            ];
        } else {    
            $mge = new MailgunEngine;

            $thisSiteLink = 'https://' . $_SERVER['SERVER_NAME'];
            $logoLink = $thisSiteLink . '/imgs/logos/scanox_logo.png';

            if ($inputs['request'] == 'approve') {
                // утверждаем заявку и если Ок - шлем письмо уведомление
                if ($spdm->approveProviderRequest($inputs['id'])) {
                    // отправляем письмо подтверждения
                    $orgName = html_entity_decode($inputs['orgName']);
                    
                    $result = $mge->sendMail(
                        [
                            'from' => ['address' => 'robot', 'name' => 'Scanox'],
                            'to' => [ $inputs['email'] => $inputs['name'] ],
                            'subject' => 'Вы прошли модерацию на Scanox!',
                            //'text' => $text,
                            'o:tracking-opens' => 'yes'
                        ],
                        [
                            'LOGOTYPE' => $logoLink,
                            'COMPANY_NAME' => $inputs['orgName'],
                            'THIS_SITE_LINK' => $thisSiteLink,
                        ],
                        'tpl_approve_moderation.html'
                    );

                    $response = [
                        'code' => 1,
                        'message' => 'Ok'
                    ];
                }
            } elseif ($inputs['request'] == 'decline') {
                // утверждаем заявку и если Ок - шлем письмо уведомление
                if ($spdm->declineProviderRequest($inputs['id'])) {
                    
                    // формируем текст комментария
                    $comment = $inputs['comment'] ?? '';
                    if ($comment) $comment = nl2br($comment);
                    
                    // отправляем письмо уведомления
                    $result = $mge->sendMail(
                        [
                            'from' => ['address' => 'robot', 'name' => 'Scanox'],
                            'to' => [ $inputs['email'] => $inputs['name'] ],
                            'subject' => 'Уведомление о модерации на Scanox',
                            //'text' => $text,
                            'o:tracking-opens' => 'yes'
                        ],
                        [
                            'LOGOTYPE' => $logoLink,
                            'COMPANY_NAME' => $inputs['orgName'],
                            'THIS_SITE_LINK' => $thisSiteLink,
                            'NEED_TO_CHANGE' => $comment
                        ],
                        'tpl_decline_moderation.html'
                    );

                    $response = [
                        'code' => 1,
                        'message' => 'Ok'
                    ];
                }
            }
        }
    } catch (SPDMException $e) {
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
        $response['message'] = "Can't change service provider request data";
    } catch (MailgunEngineException $e) {
        $log->warning($e->getMessage());
        $response['message'] = "Can't send confirmation email only";
    } finally {
        echo json_encode($response);
    }    
