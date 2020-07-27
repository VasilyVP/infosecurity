<?php # класс модели работы со справочниками загруженных временных клиентов
    namespace models;
    
    use \exceptions\MDMException;

    class ClientsDataModel
    {
        private $db;
        private $log;
        static private $object;

        private function __construct()
        {
            $this->log = \engines\LogEngine::create();

            try {
                $connect = \engines\DBConnection::create();
                $this->db = $connect->getConnection();
            } catch (\exceptions\DBException $e) {
                // если не подключилось - выбрасываем исключение
                throw new MDMException("Can't initiate correct ClientsDataModel due Db problems");
            }            
        }

        ## конструктор объекта в единственном экземпляре
        public static function create()
        {
            if (is_null(self::$object)) self::$object = new self();
            return self::$object;
        }

        # возвращает список ЧОП по строке запроса с учетом статуса (зарегистрированные или загруженные)
        public function getClientsByQuery($queryString, $status, $city = false, $useCity = true, $limit = 5)
        {
            $cityCondition = '';
            $useCityField = '';
            $useCityCondition = '';
            $useCity = $useCity == 'true' ? true : false;

            // если берем из загруженных ЧОП
            if ($status == 'temp') {
                // если фильтруем по городам
                if ($city) $cityCondition = "city = :city AND ";
                
                $query = "SELECT temp_id_client as id, active, name, phone, city, email FROM temp_clients 
                        WHERE $cityCondition (name LIKE :queryStr OR email LIKE :queryStr OR phone LIKE :queryStr) LIMIT :limit";
            } else { // если из базы загруженных ЧОП
                // учитывать ли города в запросе (сделано если сбой и город не запомнился)
                if ($useCity) {
                    
                    $useCityField = ', cities.name as city';
                    $useCityCondition = 'JOIN providers_cities_link USING (id_client) JOIN cities USING (id_city)';
                    
                    // введено ли поле город или пустое
                    if ($city) $cityCondition = "cities.name = :city AND ";
                }                

                $query = "SELECT id_client as id, uid, service_providers.active as active, display_name as name, 
                            service_providers.phone as phone, email$useCityField 
                            FROM service_providers $useCityCondition 
                            JOIN users_providers_link USING (id_client) JOIN users USING (id_user)
                            WHERE $cityCondition (display_name LIKE :queryStr OR service_providers.phone LIKE :queryStr 
                            OR email LIKE :queryStr)
                            LIMIT :limit";
            }            

            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':queryStr', "%$queryString%", \PDO::PARAM_STR);
                $stm->bindValue(':limit', $limit, \PDO::PARAM_INT);
                if ($city) $stm->bindValue(':city', $city, \PDO::PARAM_STR);
                $stm->execute();

                return $stm->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        # устанавливает статус страницы ЧОП
        // пока только в таблице временные
        public function setClientStatus($clientID, $status, $active)
        {
            if ($status == 'temp') $query = "UPDATE temp_clients SET active = :active WHERE temp_id_client = :clientID";
            else $query = "UPDATE service_providers SET active = :active WHERE id_client = :clientID";

            try {
                $stm = $this->db->prepare($query);
                $stm->execute([
                    ':active' => $active,
                    ':clientID' => $clientID
                ]);
                // возвращаем статус
                if ($stm->rowCount() == 0) return false;
                else return true;
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

    }
