<?php # Механизи работы с почтовым сервисом Mailgun
    
    namespace engines;

    use Mailgun\Mailgun;
    use exceptions\MailgunEngineException;

    class MailgunEngine
    {
        private $log;
        private $mg;
        private $headers = [];
        //private $domain;

        // инициируем логи и Mailgun компонент
        public function __construct($domain = DOMAIN_NAME)
        {
            $this->log = \engines\LogEngine::create();
            // устанавливаем домен
            //$this->domain = $domain;
            try {
                $this->mg = new Mailgun(MAILGUN_PRIVATE_API_KEY);
            } catch (\Exception $e) {
                $this->log->error("Can't init Mailgun component: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MailgunEngineException("Can't init Mailgun component");
            }
        }

        // проверяет JSON webhook на валидность
        static public function webhookVerify($signature)
        {
            // проверяем не протух ли json
            if (abs(time() - $signature->timestamp) > 15) { // 15 секунд
                return false;
            }
            // return true если signature is valid
            return hash_hmac('sha256', $signature->timestamp . $signature->token, MAILGUN_PRIVATE_API_KEY) === $signature->signature;
        }

        // отправляет одно сообщение списку адресатов
        public function sendMail($mailData, $messageData = false, $template = false, $adds = [], $domain = MAILGUN_DOMAIN)
        {
            // формируем параметры отправки сообщения
            $this->prepareData($mailData, $messageData, $template);
            try {
                $result = $this->mg->sendMessage($domain, $this->headers, $adds);
                return $result; //result->http_response_code должен быть 200
        
            } catch (\Exception $e) {
                $this->log->error("Error by email sending by Mailgun: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MailgunEngineException("Can't send email by Mailgun");
            }
        }

        // готовит данные для отправки сообщения
        private function prepareData($mailData, $messageData, $template)
        {
            foreach($mailData as $field => $value) {
                // заполняем from
                if ($field == 'from') {
                    $fromAddress = $value['address'] . '@' . MAILGUN_FROM_DOMAIN;
                    $fromName = $value['name'] ?? '';
                    $this->headers['from'] = $fromName . ' <' . $fromAddress . '>';
                // заполняем to
                } elseif ($field == 'to') {
                    // заполняем всех получателей
                    foreach($value as $email => $name) {
                        $toArr[] = $name . ' <' . $email . '>';
                    }
                    $this->headers['to'] = join(', ', $toArr);
                // формируем html тело сообщения
                } else {
                    $this->headers[$field] = $value;
                }
            }
            // если есть параметры шаблона сообщения или шаблон формируем HTML
            if ($messageData || $template) $this->headers['html'] = $this->getHtmlBody($messageData, $template);        
        }

        // заполняет шаблон сообщения
        private function getHTMLBody($messageData, $template)
        {
            // если указан шаблон, то загружаем шаблон
            if ($template) {
                $templateContent = file_get_contents(TEMPLATES_EMAIL_PATH . '/' . $template);
                if ($templateContent === false) {
                    $this->log->error("Can't load template", ['METHOD' => __METHOD__]);
                    return false;
                }
            } else $templateContent = '{MESSAGE}';
        
            // если есть поля заполнения в шаблоне - заполняем шаблон
            if ($messageData) {
                $mail = new \engines\TemplateProcessing();
                $mail->setValues($messageData);
                $mail->parseTemplate($templateContent);
                
                return $mail->getPage();
            // иначе возвращаем просто шаблон
            } else return $templateContent;        
        }

        // возвращает ссылку отписки от рассылки
        public function getUnsubscribeLink($params = [])
        {
            $domain = DOMAIN_NAME;
            $link = "https://$domain/unsubscribe?token=%recipient.unsubscribe_token%";

            if ($params['tag'] ?? false) {
                $tagEnc = base64_encode($params['tag']);
                $link .= "&tag=$tagEnc";
            }
            if ($params['mailingList'] ?? false) {
                $listEnc = base64_encode($params['mailingList']);
                $link .= "&mailingList=$listEnc";
            }

            return $link;
        }

        // проверяет ссылку отписки
        public function parseCheckUnsubscribeLink($params)
        {
            try {
                // парсим токен
                $obj = json_decode(base64_decode($params['token']));
                $signCheck = hash_hmac('sha256', $obj->email, MAILGUN_ENCRYPTION_KEY);
                // проверяем подпись в токене и email
                if ($obj->sign !== $signCheck) return false;
                // формируем ответ
                $result = new class {};
                $result->email = $obj->email;
                // если есть tag
                if ($params['tag']) $result->tag = base64_decode($params['tag']);
                else $result->tag = false;
                // если есть mailingList
                if ($params['mailingList']) $result->mailingList = base64_decode($params['mailingList']);
                else $result->mailingList = false;
                
                return $result;
            } catch (\Error $er) {
                $this->log->warning('Invalid token: ' . $er->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
            
        }

        // формирует токен для отписки (хранится в записи пользователя в Mailgun maillist)
        public function getUnsubscribeToken($email)
        {
            // формируем подпись для токена
            $sign = hash_hmac('sha256', $email, MAILGUN_ENCRYPTION_KEY);
            // сам объект
            $obj = [
                'email' => $email,
                'sign' => $sign
            ];
            $token = base64_encode(json_encode($obj));

            return $token; 
        }

        // добавляет пользователя в список расылки
        public function addUserToMailingList($listAddress, $params)
        {
            try {
                $result = $this->mg->post("lists/$listAddress/members", $params);
                return $result;
            } catch (\Exception $e) {
                $this->log->error("Can't add user to $listAddress mailing list: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MailgunEngineException("Can't add user to mailing list");
            }
        }

        /**
         * добавляет список пользователей в список расылки (до 1000)
         * $members: JSON-encoded array. Elements can be either addresses, e.g. ["bob@example.com", "alice@example.com"], 
         * or JSON objects, e.g. [{"address": "bob@example.com", "name": "Bob", "subscribed": false}, {"address": "alice@example.com", "name": "Alice"}] .
         * $upsert: yes to update existing members, no (default) to ignore duplicates
         */
        public function addManyUsersToMailingList($listAddress, $members, $upsert = 'no')
        {
            $members_JSON = json_encode($members);
            $params = [
                'members' => $members_JSON,
                'upsert' => $upsert
            ];
            try {
                $result = $this->mg->post("lists/$listAddress/members.json", $params);
                return $result;
            } catch (\Exception $e) {
                $this->log->error("Can't add user to $listAddress mailing list: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MailgunEngineException("Can't add user to mailing list");
            }
        }

        // обновляет пользователя в списке рассылки (например отписывает)
        public function updateUserAtMailingList($listAddress, $userAddress, $params)
        {
            try {
                $result = $this->mg->put("lists/$listAddress/members/$userAddress", $params);
                return $result;
            } catch (\Exception $e) {
                $this->log->error("Can't update $userAddress user at $listAddress mailing list: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MailgunEngineException("Can't update user at mailing list");
            }
        }

        // отписывает пользователя(добавляет в списки unsubscribes) от домена или tag
        public function unsubscribeUser($params)
        {
            try {
                $result = $this->mg->post(MAILGUN_DOMAIN . "/unsubscribes", $params);
                return $result;
            } catch (\Exception $e) {
                $email = $params['address'] ?? false;
                $tag = $params['tag'] ?? false;
                $this->log->error("Can't unsubscribe $email from domain or tag='$tag': " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MailgunEngineException("Can't unsubscribe user from domain or tag");
            }
        }

        // возвращает списки рассылок
        public function getMailingLists()
        {
            try {
                $result = $this->mg->get('lists/pages');
                if ($result->http_response_code == 200) {
                    foreach($result->http_response_body->items as $value) {
                        $list[] = (object)[
                            'description' => $value->description,
                            'address' => $value->address
                        ];
                    }
                    return $list;
                } else throw new MailgunEngineException("Don't recive mailing lists");                
            } catch (\Exception $e) {
                $this->log->error("Can't get maling lists: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MailgunEngineException("Can't get mailing lists");
            }
        }

        // возвращает список меток tag для домена
        public function getTagsList()
        {
            try {
                $result = $this->mg->get(MAILGUN_DOMAIN . '/tags');
                if ($result->http_response_code == 200) {
                    foreach($result->http_response_body->items as $value) {
                        $list[] = $value->tag;
                    }
                    return $list;
                } else throw new MailgunEngineException("Don't recive tags list");                
            } catch (\Exception $e) {
                $this->log->error("Can't get tags list: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MailgunEngineException("Can't get tags list");
            }
        }

        // получает события Mailgun
        public function getEvents($params)
        {
            try {
                $result = $this->mg->get(MAILGUN_DOMAIN . '/events', $params);

                //$this->log->debug(print_r($result, true));
                
                /*
                if ($result->http_response_code == 200) {
                    foreach($result->http_response_body->items as $value) {
                        $list[] = $value->tag;
                    }
                    return $list;
                } else throw new MailgunEngineException("Don't recive tags list");
                */
                return $result;

            } catch (\Exception $e) {
                $this->log->error("Can't get events list: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MailgunEngineException("Can't get events list");
            }

        }

    }

/* using examples
    try {
        $mge = new MailgunEngine;

        $result = $mge->sendMail(
            [
                'from' => ['address' => 'robot', 'name' => 'SCANOX'],
                'to' => [
                    'test_list@mg.findsecurity.info' => '%recipient_name%',
                   // 'vasilyvp@list.ru' => 'Vasily Popov'
                ],
                'subject' => 'SCANOX mailing test',
                'text' => 'Mail body text текст',
                
                //'recipient-variables' => '{"vasilyvp@list.ru": {"name": "Vasily Popov", "id":1},
                //                           "vasilyvp@gmail.com": {"name": "Popov", "id": 2}}',                
                'o:tag' => ['mailing_test'],
                'o:tracking-opens' => 'yes',
                //'o:tracking-clicks' => 'yes',
                //'o:deliverytime' => time() + 360,
            ],
            [ 'MESSAGE' => '<html><body>Mail body html<br>
                            Your age is %recipient.age%<br>
                            <a href="development.findsecurity.info">Click</a><br>Подпись: Василий Vasily.
                            <a href="%mailing_list_unsubscribe_url%">Отписаться</a></body></html>' ],
            false,
            [
             //   'attachment' => [ 'imgs/blazon.jpg', 'imgs/team.jpg' ]
            ]
        );
    } catch (\exceptions\MailgunEngineException $e) {
        echo $e->getMessage();
    }
    
    try {
        $mge = new MailgunEngine;

        $result = $mge->unsubscribeUser([
            'address' => 'vasilyvp@gmail.com',
          //  'tag' => 'testListTag'
        ]);

    } catch (\exceptions\MailgunEngineException $e) {
        echo $e->getMessage();
    }

    try {
        $mge = new MailgunEngine;

        $email = 'vasilyvp@gmail.com';
        $name = 'Popov Vasily';
        $vars = [
            'unsubscribe_token' => $mge->getUnsubscribeToken($email)
        ];

        $result = $mge->addUserToMailingList('test_list@mg.scanox.pro', [
            'address' => $email,
            'name' => $name,
            'subscribed' => true,
            'vars' => json_encode($vars)
        ]);

    } catch (\exceptions\MailgunEngineException $e) {
        echo $e->getMessage();
    }

try {
    $mge = new MailgunEngine;

    $result = $mge->updateUserAtMailingList('test_list@mg.findsecurity.info', 'vasilyvp@hotmail.com');
    $result = $mge->updateUserAtMailingList('temp_clients@mg.scanox.pro', $email, ['subscribed' => false]);

} catch (MailgunEngineException $e) {
    echo $e->getMessage();
}

    echo '<pre>';
    print_r($result);
    echo '</pre>';
*/
