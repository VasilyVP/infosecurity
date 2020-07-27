<?php # сервис работы с рассылками
    use \exceptions\MDMException;
    use \exceptions\MailgunEngineException;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // статус по умолчанию - ошибка
    $status = [
        'code' => 0,
        //'message' => 'Error or size exceed'
    ];

    // проверяем авторизацию и роль
    $auth = \engines\AuthenticationEngine::create();
    if (!$auth->isAuthenticated() || $auth->getUserRole() != 'admin') {
        $log->warning('Attempt to send message without authorization', ['METHOD' => __METHOD__]);
        $status['message'] = 'Unuthorized';
        echo json_encode($status);
        exit();
    }
    
    // обрабатываем загрузку шаблона
    if (count($_FILES) > 0) {
        foreach($_FILES as $key => $value) {
            // если файл - шаблон
            if ($key == 'template') {
                $template = $value;

                if (($template['error'] !== UPLOAD_ERR_OK) || ($template['size'] > 1000000)) {
                    $log->error("Can't upload file or size exceed", ['METHOD' => __METHOD__]);
                    $status['message'] = "Can't upload file or size exceed";
                // перемещаем файл и шлем статус
                } elseif (move_uploaded_file($template['tmp_name'], TEMPLATES_MAILINGS_PATH . $template['name'])) {
                    $status = [
                        'code' => 1,
                        'message' => 'Template Ok'
                    ];
                } else {
                    $log->error("Error by moving uploaded template", ['METHOD' => __METHOD__]);
                    $status['message'] = 'Template error';
                }
            // если файл - картинка
            } else {
                $img = $value;
                if(($img['error'] !== UPLOAD_ERR_OK) || ($img['size'] > 1000000)) {
                    $log->error("Can't upload file or size exceed", ['METHOD' => __METHOD__]);
                    $status['message'] = "Can't upload file or size exceed";
                // перемещаем файл и шлем статус
                } elseif (move_uploaded_file($img['tmp_name'], MAILINGS_IMGS_PATH . $img['name'])) {
                    $status['code'] = 1;    
                    $status['message'] = 'Images Ok';
                } else {
                    $log->error("Error by moving uploaded image", ['METHOD' => __METHOD__]);
                    $status['message'] = "Can't move uploaded images";
                }
            }
        }          
        echo json_encode($status);
        exit();
    }

    // обрабатываем запросы данных
    $response = '';
    if ($_GET['get'] ?? false) {
        // список шаблонов
        if ($_GET['get'] == 'templates') {
            $dir = scandir(TEMPLATES_MAILINGS_PATH);
            foreach ($dir as $value) {
                if (!is_file(TEMPLATES_MAILINGS_PATH . $value)) continue;
                $response .= "<option value=\"$value\">$value</option>\n";
            }        
        }
        // история рассылок
        if($_GET['get'] == 'mailingsHistory') {
            try {
                $mdm = \models\MailingsDataModel::create();
                $list = $mdm->getMailingsList();
                if ($list) $response = json_encode($list);
                else $response = json_encode('empty list');
            } catch (MDMException $e) {
                $response = json_encode('error');
            }
        }
        
        try {
            $mge = new \engines\MailgunEngine();

            // обрабатываем запрос на список рассылок
            if ($_GET['get'] == 'mailings') {
                $list = $mge->getMailingLists();
                foreach($list as $item) $response .= "<option value=\"$item->address\">$item->description</option>\n";
            }
            
            // обрабатываем запрос на метки tag
            if ($_GET['get'] == 'tags') {
                $list = $mge->getTagsList();
                foreach($list as $tag) $response .= "<option value=\"$tag\">\n";
            }
        } catch (MailgunEngineException $e) {
            $response = "<option>error</option>\n";
        }

        echo $response;
        exit();
    }

    // обрабатываем запрос рассылки
    if ($_POST['mailing_template'] ?? false) {

        // фильтруем вход
        $inputs = filter_input_array(INPUT_POST, [
            'mailing_template' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => [ 'regexp' => '/^[a-z0-9_-]{1,50}(\.html)|(\.htm)$/iu' ]
            ],
            'mailing_list' => FILTER_VALIDATE_EMAIL,
            'mailing_from' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => [ 'regexp' => '/^[a-z0-9_.-]{1,30}$/iu' ]
            ],
            'mailing_tag' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => [ 'regexp' => '/^[a-z0-9_.-]{1,30}$/iu' ]
            ],
            'mailing_subject' => FILTER_SANITIZE_STRING,
            'mailing_description' => FILTER_SANITIZE_STRING,
            'from_name' => FILTER_SANITIZE_STRING
        ]);
        // если пустой - выходим
        if (!is_array($inputs)) exit();

        // если есть несоответствующее значение - выходим
        if (in_array(false, $inputs, true)) {
            $log->warning('Incorrect inputs', ['METHOD' => __FILE__]);
            $status['message'] = 'Incorrect inputs';
            echo json_encode($status);
            exit();
        }

        try {
            $status['message'] = 'some error';

            $mge = new \engines\MailgunEngine();

            $result = $mge->sendMail(
                [
                    'from' => [
                        'address' => $inputs['mailing_from'], 'name' => $inputs['from_name']
                    ],
                    'to' => [ $inputs['mailing_list'] => '%recipient_name% %recipient_surname%' ],
                    'subject' => $inputs['mailing_subject'],
                    'text' => 'Only HTML body exist',
                    'o:tag' => [$inputs['mailing_tag']],
                    'h:List-Unsubscribe' => '<' . $mge->getUnsubscribeLink([ 'mailingList' => $inputs['mailing_list'] ]) . '>',
                    'o:tracking-opens' => 'yes',
                    'o:tracking-clicks' => 'yes'
                ],
                [
                    'DOMAIN_UNSUBSCRIPTION_LINK' => $mge->getUnsubscribeLink(),
                    'TAG_UNSUBSCRIPTION_LINK' => $mge->getUnsubscribeLink([ 'tag' => $inputs['mailing_tag'] ]),
                    'MAILINGLIST_UNSUBSCRIPTION_LINK' => $mge->getUnsubscribeLink([ 'mailingList' => $inputs['mailing_list'] ]),
                ],
                'mailings/' . $inputs['mailing_template'],
                [],
                MAILGUN_MAILING_DOMAIN
            );
            if ($result->http_response_code == 200) {
                $status = [
                    'code' => 1,
                    'message' => 'Successfully queued'
                ];
                
                // добавляем запись в БД
                try {
                    $mdm = \models\MailingsDataModel::create();
                    if ($mdm->addMailing($inputs['mailing_list'], $inputs['mailing_template'],
                                         $inputs['mailing_tag'], $inputs['mailing_description'])) {
                        $status['db'] = 'Record added';
                    }
                } catch (\exceptions\MDMExeption $e) {
                    $log->error('Error: ' . $e->getMessage());
                    $status['db'] = "Can't add record";
                }

                return;
            } else {
                $status['message'] = 'Mailgun error: ' . $result->http_response_body->message;
                return;
            }
        } catch (\exceptions\MailgunEngineException $e) {
            $log->error("Can't start mailing: " . $e->getMessage());
            $status['message'] = "Can't start mailing by Mailgun";
            return;
        } catch (Exception | Error $e) {
            $status['message'] = 'Not caught exception: ' . $e->getMessage();
        } finally {
            echo json_encode($status);
        }        
    }
