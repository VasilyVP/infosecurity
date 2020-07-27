<?php # класс модели работы со справочниками стран и городов (не все функции)
    namespace models;
    use exceptions\DBException, exceptions\MDMException, exceptions\SPDMException;
    use utilities\funcsLib;

    class AddressesDataModel
    {
        private $db, $log;
        static private $object;

        private function __construct()
        {
            $this->log = \engines\LogEngine::create();

            try {
                $connect = \engines\DBConnection::create();
                $this->db = $connect->getConnection();
            } catch (DBException $e) {
                // если не подключилось - выбрасываем исключение
                throw new MDMException("Can't initiate correct ServiceProvidersDataModel due Db problems");
            }            
        }

        ## конструктор объекта в единственном экземпляре
        public static function create()
        {
            if (is_null(self::$object)) self::$object = new self();
            return self::$object;
        }

        /** Возвращает страну из БД на русском по транслиту */
        public function getCountry($country)
        {
            $query = "SELECT public_name FROM countries WHERE public_name_en = :nameTemplate";

            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':nameTemplate', $country, \PDO::PARAM_STR);
                $stm->execute();

                return $stm->fetchColumn();
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        /** Возвращает город из БД на русском по транслиту */
        public function getFullAddress($addrComp)
        {
            if (!($addrComp['city'] ?? false)) return '';            

            $query = "SELECT * FROM cities WHERE name_en = :nameTemplate";

            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':nameTemplate', $addrComp['city'], \PDO::PARAM_STR);
                $stm->execute();

                $cities = $stm->fetchAll(\PDO::FETCH_ASSOC);
            
                // проверяем есть ли результат
                if (!$cities) return '';
                
                // проверяем область
                if ($addrComp['region'] ?? false) {
                    // сравниваем множество городов на соответствие региону
                    foreach($cities as $city) {
                        if ($addrComp['region'] == mb_strtolower($city['region_en'])) {
                            // если район есть - его тоже проверяем и формируем массив элементов
                            if (($addrComp['area'] ?? false) && $addrComp['area'] == mb_strtolower($city['area_en']))
                                $arrFull = [$city['name'], $city['area'], $city['region']];
                            else
                                $arrFull = [$city['name'], $city['region']];
                            return join(', ', $arrFull);
                        }
                    }                        
                } else return $cities[0]['name'];
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        /** Возвращает список городов */
        public function getCitiesByQuery($queryStr, $status, $limit = 5)
        {
            if ($status == 'temp') {
                $query = "SELECT DISTINCT city FROM temp_clients WHERE city LIKE :queryStr LIMIT :limit";
            } else {
                $query = "SELECT DISTINCT name as city FROM cities WHERE name LIKE :queryStr LIMIT :limit";
            }
            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':queryStr', "$queryStr%", \PDO::PARAM_STR);
                $stm->bindValue(':limit', $limit, \PDO::PARAM_INT);
                $stm->execute();

                $result = $stm->fetchAll(\PDO::FETCH_ASSOC);

                return $result;
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        /** Итератор, возвращает список Топ городов по регистрации ЧОПОВ */
        public function getTopCities($limit)
        {
            $query = "SELECT cities.name AS city, name_en AS cityEn, count(id_client) AS quantity, public_name_en AS countryEn
                        FROM cities JOIN providers_cities_link USING (id_city) JOIN countries USING (id_country)
                        JOIN service_providers USING (id_client)
                        WHERE service_providers.active = 1
                        GROUP BY city, cityEn, countryEn ORDER BY quantity DESC LIMIT $limit";

            try {
                foreach ($this->db->query($query, \PDO::FETCH_OBJ) as $cityObj) yield $cityObj;
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MDMException("Can't read cities list");
            }
        }

    }
