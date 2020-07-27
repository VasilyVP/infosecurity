<?php # компонент проверки робота на странице поиска при переходе с главной
        
    namespace components;

    use engines\LogEngine, engines\CheckRobots, engines\RoutingEngine;

    class CheckRobotsComponent
    {
        private $log;
        private $routing;
        private $acceptedRobots;

        public function __construct()
        {
            $this->log = LogEngine::create();
            $this->routing = \engines\RoutingEngine::create();            
        }

        /** Возвращает результат проверки на робота по строке запроса search($query) (например 'citiesList')
         * 1) если user agent поисковик, то проверяем на достоверность поисковик по доменному имени
         * 2) если нет, то :
         *      а) проверяем на SSR
         *      b) если не SSR - проверяем достоверность captcha
         */
        public function getBotCheckByQueryStr($query)
        {
            // если в строке запроса нет требуемого критерия проверки - возвращаем true - прошли проверку
            if (!in_array($query, $this->routing->getQueryArr())) return true;
            
            // подключаем Engine проверки на роботов со ссылкой на captcha main_page
            $cr = new \engines\CheckRobots('main_page');
            
            // иначе проверяем UserAgent:
            $ua = $cr->checkUserAgent();

            // если не поисковый робот - проверяем капчу
            if ($ua->type == 'common') {
                // если это SSR - возвращаем true
                if ($_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR']) return true;

                // проверяем captcha
                $check = $cr->getCheckByCaptcha('main_page');
                
                $status = $check->status ?? false; // добавил т.к. status при ошибке отсутствует

                if ($status == 'human') return true;
                else {
                    //$this->log->warning('Robot detected. Score: ' . $check->score, ['METHOD' => __METHOD__]);
                    return false;
                }
            // если поисковый робот - проверяем его имя
            } elseif ($ua->type == 'searchRobot') 
                if ($cr->checkSearchRobot($ua->name)) return true;
                else {
                    $this->log->warning('Robot detected', ['METHOD' => __METHOD__]);
                    return false;
                }
        }

    }
