<?php # класс модели работы с информацией о рассылках в БД
    namespace models;

    class MailingsDataModel
    {
        private $log;
        private $db;
        static private $object;

        private function __construct()
        {
            $this->log = \engines\LogEngine::create();

            try {
                $connect = \engines\DBConnection::create();
                $this->db = $connect->getConnection();
            } catch (\exceptions\DBException $e) {
                // если не подключилось - выбрасываем исключение
                throw new \exceptions\MDMException("Can't initiate correct MailingsDataModel due Db problems");
            }            
        }

        ## конструктор объекта в единственном экземпляре
        public static function create()
        {
            if (is_null(self::$object)) self::$object = new self();
            return self::$object;
        }

        # добавляет рассылку в БД
        public function addMailing($mailingList, $template, $tag = '', $description = '')
        {
            $query = "INSERT INTO mailings VALUES (null, null, :mailingList, :template, :tag, :description)";
            $response = false;
            try {
                $stm = $this->db->prepare($query);
                $stm->execute([
                    ':mailingList' => $mailingList,
                    ':template' => $template,
                    ':tag' => $tag,
                    ':description' => $description
                ]);

                if ($stm->rowCount() !== 1) {
                    $this->log->error("Can't insert new user", ['METHOD' => __METHOD__]);
                    throw new RuntimeException("Can't insert new mailing record to database");
                } else {
                    $response = true;
                }
            } catch (\PDOException $e) {
                $this->log->error('SQL request error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new \exceptions\MDMException("Can't insert new mailing record to database");
            } finally {
                return $response;
            }
        }
        
        # возвращает список осуществленных рассылок
        public function getMailingsList()
        {
            $query = "SELECT * FROM mailings";
            try {
                $stm = $this->db->query($query);
                return $stm->fetchAll(\PDO::FETCH_NUM);
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

    }
