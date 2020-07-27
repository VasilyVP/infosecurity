<?php # Класс подключения к БД
    namespace engines;

    class DBConnection
    {
        private $pdo;
        private static $object;

        ## создаем объект в единственном экземпляре
        private function __construct()
        {
            $log = \engines\LogEngine::create();
            try {
                $host = HOST_NAME;
                $dbname = DB_NAME;
                $user = DB_USER_NAME;
                $password = DB_USER_PASSWORD;
                
                $this->pdo = new \PDO(
                    "mysql:host=$host;dbname=$dbname;charset=UTF8",
                    $user,
                    $password,
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_PERSISTENT => true,
                    ]
                );
            } catch (\PDOException $e) {
                // записываем в лог
                $log->error("Can't connect to MySQL: Host - $host; Database - $dbname; User - $user. Error: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                // выбрасываем исключение DBException
                throw new \exceptions\DBException("Can't initiate PDO object");
                //exit();
            }
        }

        ## конструктор объекта в единственном экземпляре
        public static function create()
        {
            if (is_null(self::$object)) self::$object = new self();

            return self::$object;
        }

        ## возвращает соединение
        public function getConnection()
        {
            return $this->pdo;
        }

    }
