<?php ## Механизм подготовки входящих данных
    namespace engines;

    class Inputs
    {
        static private $object;
        public $search = false;
        public $chopPage = false;
        public $chopPAPage = false;
        public $testPage = false;
        public $login = false;
        public $password = false;
        public $rememberme = false;
        //public $USER = false;
        public $AKEY = false;

        // инициирует все входные переменные
        private function __construct()
        {
            // подключаем логи
            //$this->log = \engines\LogEngine::create();

            // проверяем входные переменные GET
            if (count($_GET) >0 ) {
                $this->checkSearchExp();
                $this->checkGetChopPage();
                $this->checkGetChopPAPage();
                $this->checkTestPage();
            }
            // проверяем входные переменные POST
            if (count($_POST) > 0) {
                $this->checkLogin();
            }
            // проверяем входные куки
            if (count($_COOKIE) > 0) {
                $this->checkCredentials();
            }
        }

        // создаем объект в единственном экземпляре
        static public function create()
        {
            if (is_null(self::$object)) self::$object = new self();

            return self::$object;
        }

        // проверяем запрос на аутентификацию
        private function checkLogin()
        {
            // надо сделать проверку логинов и паролей!!!
            $this->login = $_POST['login'] ?? false;
            $this->password = $_POST['password'] ?? false;
            $this->rememberme = $_POST['rememberme'] ?? false;
        }
        
        // проверяем наличие USER и AKEY
        private function checkCredentials()
        {
            //$this->USER = $_COOKIE['USER'] ?? false;
            $this->AKEY = $_COOKIE['AKEY'] ?? false;
        }
        
        // !!! проверяем поисковую строку !!! НАДО СДЕЛАТЬ нормальную проверку !!!
        private function checkSearchExp()
        {
            $this->search = $_GET['search'] ?? false;
            /*
            if ($searchExp == 'yes')
                $this->search = true;
            else
                $this->search = false;
                */
        }
        
        # проверяем запрос на страницу ЧОП
        private function checkGetChopPage()
        {
            $this->chopPage = $_GET['chop'] ?? false;
            //$this->chopPage = ($chopPage == 'orel') ? true: false;
            
        }

        # проверяем запрос на страницу ЛК ЧОП
        private function checkGetChopPAPage()
        {
            $this->chopPAPage = $_GET['choppapage'] ?? false;
        }

        # проверяем запрос тестовой страницы
        private function checkTestPage()
        {
            $this->testPage = $_GET['testpage'] ?? false;
        }


## ----------------------Это примеры проверок значений -------------------------------------
        // проверяем страницу навигации списка препаратов
        private function checkPage()
        {
            $page = $_GET['page'] ?? false;
            if ($page && ($page < 20))
                $this->page = $page;
        }

        // проверяем наименование препарата
        private function checkDrugName()
        {
            $drugName = $_GET['drug_name'] ?? false;
            if ($drugName && mb_strlen($drugName) < 50)
                $this->drugName = urldecode($drugName);
               // $this->log->debug("drugName= $this->drugName", ['METHOD' => __METHOD__]);
        }

        // проверяем ID препарата
        private function checkDrugID()
        {
            $drugID = $_GET['drug_id'] ?? false;
            if ($drugID && ($drugID >= 0) && ($drugID < 1000000))
                $this->drugID = $drugID;
        }

        // проверяет нажатую клавишу Алфавитного указателя
        private function checkPressedLetter()
        {
            $pressedLetter = $_GET['pressed_letter'] ?? false;
            if ($pressedLetter && is_string($pressedLetter) && mb_strlen($pressedLetter) == 1)
                $this->pressedLetter = urldecode($pressedLetter);
        }

    }
