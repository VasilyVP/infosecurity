<?php ## класс маршрутизации страниц    
    namespace engines;

    //use GuzzleHttp\Client;

    class RoutingEngine
    {
        private $inputs;
        private $auth;
        //private $authenticated;
        private $routArr; // массив элементов из URL path
        private $query; // - строка параметров URL после ?
        private $queryArr; // массив с параметрами из query
        //private $pageName; // запрошенная страница
        private $addrComp; // компоненты адреса country, city, area, region
        private $log;
        private $analytics; // хранит признак подключения аналитики для текущей страницы
        private static $object;

        // формирует разбор URI при инициализации
        private function __construct()
        {
            $this->log = \engines\LogEngine::create();
            
            // проверяем, есть ли аутентификация
            $this->auth = \engines\AuthenticationEngine::create();

            // разбираем пути
            $this->parseURI();

            // формируем признак аналитики
            $this->analytics = ROUTS[$this->routArr[0]]['analytics'] ?? false;
        }

        # конструктор объекта в единственном экземпляре
        static public function create()
        {
            if (is_null(self::$object)) self::$object = new self();
            
            return self::$object;
        }

        /** Возвращает требуемый шаблон страницы по адресу и проверяет права по роли пользователя и шаблону */
        public function getPage()
        {
            // проверяем доступен ли сайт
            if (SITE_UNDER_MAINTENANCE) return TPL_UNDER_MAINTENANCE_PAGE;

            $auth = $this->auth;
            
            // если Logout
            if (($this->routArr[0] == 'logout') && $auth->isAuthenticated()) {
                $auth->logout();
                header('Location: /');
                exit();
            }

            // проверяем на существование маршрут
            $rout = ROUTS[$this->routArr[0]] ?? false;

            // если страница поиска больше 20
            $res = preg_match('/page=(\d+)/', $this->query, $matches);
            $bigPage = ($res && $matches[1] > 20) ? true : false;

            // если страница поиска, но без города
            $emptySearch = $this->routArr[0] == 'search' && (!($this->routArr[1] ?? false) || !($this->routArr[2] ?? false));
            
            // сама проверка
            if (!$rout || $emptySearch || $bigPage) {                
                // страница не найдена
                header('HTTP/1.0 404 Not Found');
                // редирект на главную страницу
                header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']);
                exit();
            }

            # -- блок "серверный рендеринг" --
            // проверяем: Поисковый робот или нет
            $cr = new CheckRobots;
            // проверяем входит ли UA в список особо обрабатываемых роботов
            $ua = $cr->checkUserAgent();

            // если UA входит
            if ($ua->type != 'common') {
                // проверяем достоверность UA и домена
                if ($cr->checkSearchRobot()) {
                    // если это бот поисковой системы - возвращаем страницу через рендеринг
                    $ssr = new ServerRenderingEngine();
                    return $ssr->getPage($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                } else {
                    // если UA поддельный
                    header('HTTP/1.1 403 Forbidden');
                    echo 'Unauthorized access';
                    //header('Location: /');
                    exit();
                }
            }
            # -- окончание блока "серверный рендеринг"

            // остальные маршруты
            $page = ROUTS[$this->routArr[0]]['page'];
            $role = ROUTS[$this->routArr[0]]['role'];

            // инициируем управление кешированием
            $cachControl = new CachControlEngine(ROUTS[$this->routArr[0]]);

            // если общедоступная странница - просто ее возвращаем
            if ($role === 'everybody') return $page;
            
            // иначе проверяем авторизацию и соответствие роли
            if ($auth->isAuthenticated() && strpos($role, $auth->getUserRole()) !== false) {
                return $page;
            }
            
            // по умолчанию редирект на главную страницу
            //header('HTTP/1.1 403 Forbidden');
            //echo 'Unauthorized access';
            header('Location: /');
            exit();
        }

        /** возвращает домашнюю страницу аутентифицированного пользователя */
        public function getHomePage()
        {
            return HOME_BY_ROLES[$this->auth->getUserRole()];
        }

        // разбирает URI для маршрутизации
        private function parseURI()
        {
            $url = $_SERVER['REQUEST_URI'] ?? false;
            // проверяем валидность url
            $url = filter_var($url, FILTER_SANITIZE_URL);

            if (!$url) return false;            

            $rout = parse_url($url);
            
            // запоминаем массив с маршрутом и параметры query
            $this->routArr = explode('/', trim($rout['path'], '/'));
            $this->query = $rout['query'] ?? false;

            // разбираем адресную строку в переменные
            parse_str($this->query, $this->queryArr);

            // добавляем имя страницы
            //$this->pageName = $this->routArr[0];            
        }

        /** Преобразовывает routArr[] из URL на страну и т.д. в транслите */
        public function parseAddressURL($routArr = false)
        {
            $routArr = $routArr ? $routArr : $this->routArr;

            // убираем первый компонент - название страницы
            array_shift($routArr);
            // здесь собираем компоненты адреса
            $addrComp = [];

            $count = count($routArr);
            // если нет компонетов адреса
            if ($count === 0) return false;

            $addrComp['country'] = $routArr[0];
            
            // если есть город и т.д.
            if ($count == 2) {
                // заменяем в строке "_" на пробелы
                $addrFull = str_replace('_', ' ', $routArr[1]);

                // формируем массив из элементов адреса
                $addrArr = explode('~', $addrFull);

                $addrComp['city'] = $addrArr[0];
                if (count($addrArr) == 2) $addrComp['region'] = $addrArr[1];
                if (count($addrArr) == 3) {
                    $addrComp['area'] = $addrArr[1];
                    $addrComp['region'] = $addrArr[2];
                }
            }            
            // запоминаем в объекте
            $this->addrComponents = $addrComp;

            return $addrComp;
        }

        /** Возвращает routArr */
        public function getRoutArr()
        {
            return $this->routArr;
        }

        /** Возвращает queryArr */
        public function getQueryArr()
        {
            return $this->queryArr;
        }

        /** Возвращаем analytics - признак подключения аналитики на страницу */
        public function getAnalytics()
        {
            return $this->analytics;
        }

    }
