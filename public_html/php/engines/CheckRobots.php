<?php /** Engine проверки на роботов по User agent, проверки поискового робота по домену и ip,
        * подписаной куке или токену reCAPTCHA v3
        */
    namespace engines;

    class CheckRobots
    {
        private $log;
        private $token;
        private $action;

        public function __construct() //$action, $token = null
        {
            $this->log = \engines\LogEngine::create();

            //$this->token = $token ?? $_COOKIE['captchaToken'] ?? false;
            //$this->action = $action;

            //разрешенные User agents strings
            $this->acceptedRobotsUA = [
                // scanox
                'Scanox cache initiator',
                // google
                'APIs-Google (+https://developers.google.com/webmasters/APIs-Google.html)',
                'Mediapartners-Google',
                'Mozilla/5.0 (Linux; Android 5.0; SM-G920A) AppleWebKit (KHTML, like Gecko) Chrome Mobile Safari (compatible; AdsBot-Google-Mobile; +http://www.google.com/mobile/adsbot.html)',
                'Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1 (compatible; AdsBot-Google-Mobile; +http://www.google.com/mobile/adsbot.html)',
                'AdsBot-Google (+http://www.google.com/adsbot.html)',
                'Googlebot-Image/1.0',
                'Googlebot-News',
                'Googlebot-Video/1.0',
                'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Safari/537.36',
                'Googlebot/2.1 (+http://www.google.com/bot.html)',
                'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.96 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                //'(Various mobile device types) (compatible; Mediapartners-Google/2.1; +http://www.google.com/bot.html)'
                'AdsBot-Google-Mobile-Apps',
                'AdsBot-Google-Mobile-Apps',
                'FeedFetcher-Google; (+http://www.google.com/feedfetcher.html)',
                // yandex
                'Mozilla/5.0 (compatible; YandexAccessibilityBot/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexAdNet/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexBot/3.0; MirrorDetector; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexCalendar/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexDirect/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexDirectDyn/1.0; +http://yandex.com/bots',
                'Mozilla/5.0 (compatible; YaDirectFetcher/1.0; Dyatel; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexForDomain/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexImages/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexImageResizer/2.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (iPhone; CPU iPhone OS 8_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B411 Safari/600.1.4 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (iPhone; CPU iPhone OS 8_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B411 Safari/600.1.4 (compatible; YandexMobileBot/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexMarket/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexMedia/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexMetrika/2.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexMetrika/2.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexNews/4.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexOntoDB/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexOntoDBAPI/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexPagechecker/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexSearchShop/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexSitelinks; Dyatel; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexSpravBot/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexTurbo/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexVertis/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexVerticals/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexVideo/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexVideoParser/1.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (compatible; YandexWebmaster/2.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36 (compatible; YandexScreenshotBot/3.0; +http://yandex.com/bots)',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36 (compatible; YandexMedianaBot/1.0; +http://yandex.com/bots)',
                // facebook
                'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
                'facebookexternalhit/1.1',
                'Facebot',
                // twitter
                'Twitterbot'
            ];
            // разрешенные домены роботов
            $this->acceptedRobotsDomains = [
                'yandex.ru', 'yandex.net', 'yandex.com',
                'googlebot.com', 'google.com',
                'facebook.com',
                'twitter.com'
            ];
        }

        // возвращает результат проверки токена
        public function getCheckByCaptcha($action, $token = null)
        {
            // если локально - всегда возвращает 'human'
            if (!HOSTING) return (object)['status' => 'human'];

            $this->token = $token ?? $_COOKIE['captchaToken'] ?? false;
            $this->action = $action;

            // если токена нет - возвращаем false
            if ($this->token === false) return false;

            // формируем строку запроса
            $query = PATHS['reCAPCHAsiteVerify'];

            // формирует запрос
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $query,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_PROXYPORT => 3128,
                CURLOPT_POSTFIELDS => [
                    'secret' => CAPCHA_SECRET_KEY,
                    'response' => $this->token,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ]
            ]);
            // получаем ответ
            $response = curl_exec($ch);
            curl_close($ch);

            return $this->checkCaptchaResponse((object)json_decode($response));
        }

        // анализирует ответ гугл и возвращает результат проверки (критерий - 0.5)
        private function checkCaptchaResponse($respObj)
        {            
            if ($respObj->success) {
                if ($respObj->hostname == $_SERVER['SERVER_NAME'] && $respObj->action == $this->action && $respObj->score > 0.5) {
                    return (object)[
                        'status' => 'human',
                        'score' => $respObj->score
                    ];
                }
            } else {
                if (property_exists($respObj, 'error-codes')) {
                    foreach($respObj->{'error-codes'} as $error)
                        $this->log->warning('Unsuccess reCAPTCHA resolve. Error: ' . $error, ['METHOD' => __METHOD__]);
                    
                    return (object)[
                        'status' => 'robot',
                        'score' => null
                    ];
                }                    
            }

            return (object)[
                'status' => 'robot',
                'score' => $respObj->score
            ];
        }

        /** Проверяет есть ли User Agent в списке допустимых ботов */
        public function checkUserAgent()
        {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? false;

            // проверяем принадлежит ли User agent кому-то из списка разрешенных роботов
            if (!in_array($userAgent, $this->acceptedRobotsUA)) return (object)['type' => 'common'];
            else return (object)['type' => 'searchRobot', 'name' => $userAgent];
        }

        /** Проверяет робота: определяет домен по IP, сравнивает с допустимыми доменами, проверяет обратно IP (обратно не проверяем) */
        public function checkSearchRobot() // $name
        {
            // если это робот scanox - возвращаем true
            if ($_SERVER['HTTP_USER_AGENT'] == 'Scanox cache initiator') return true;
            
            // получаем домен робота
            $host = gethostbyaddr($_SERVER['REMOTE_ADDR']);

            // получаем домен 2го уровня из хоста
            $host_arr = explode('.', $host);
            $domain = join('.', array_slice($host_arr, -2));

            // проверяем его на допустимость
            if (in_array($domain, $this->acceptedRobotsDomains)) {
                return true;
            } else return false;
        }

    }
