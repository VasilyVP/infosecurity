<?php # класс модели работы со справочниками Сервис-Провайдеров (ЧОП)
    namespace models;
    use exceptions\DBException, exceptions\MDMException, exceptions\SPDMException, exceptions\UtilsException;
    use utilities\funcsLib;

    class ServiceProvidersDataModel
    {
        private $db, $log;
        static private $object;
        // данные Сервис-провайдера(ЧОП)
        private $chopID, $inputs;

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

        /** Метод возвращает id_client по id_user или false, если его нет */
        public function getServiceProviderByUserID($userID)
        {
            $query = "SELECT id_client FROM users_providers_link WHERE id_user = :userID";
            try {
                $stm = $this->db->prepare($query);
                $stm->execute([':userID' => $userID]);
                $chop = $stm->fetchObject();
                // возвращаем id_client если есть
                return  $chop ? $chop->id_client : false;
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new MDMException('Db error');
            }
        }

        /** Метод создает запись Клиента, связь с Пользователем и папку в imgs */
        public function createServiceProviderRecord($userID)
        {
            $query1 = "INSERT INTO service_providers (id_client, uid) VALUES(null, MD5(UUID()))";
            $query2 = "INSERT INTO users_providers_link VALUES(:clientID, :userID)";
            try {
                $this->db->beginTransaction();
                
                // вставляем новый id_client
                if ($this->db->exec($query1) == 0) throw new \Exception("Can't insert new id_client");
                
                // формируем новую связь Пользователь-ЧОП
                $clientID = $this->db->lastInsertId();
                
                $stm = $this->db->prepare($query2);
                if (!$stm->execute(
                    [
                        ':clientID' => $clientID,
                        ':userID' => $userID                    
                    ]))
                    throw new \Exception("Can't insert new link to users_providers_link");

                // проверяем есть ли уже такая папка ЧОП и если нет
                if (!is_dir(SERVICE_PROVIDERS_IMGS_PATH . "/$clientID")) {
                    // создаем папку ЧОПА в imgs
                    if (!mkdir(SERVICE_PROVIDERS_IMGS_PATH . "/$clientID", 0744)) throw new \Exception("Can't create folder");
                }                

               // коммитим транзакцию
               if (!$this->db->commit()) throw new \Exception("Can't commit transaction");
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            } catch (\Exception $e) {
                // откатываем транзакцию и логируем
                $this->log->error('Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                $this->db->rollBack();
                return false;
            }
            
            return $clientID;
        }

        /** Возвращает количество файлов в папке ЧОП */
        public function getFilesQuantity($chopID)
        {
            try {
                $dirList = scandir(SERVICE_PROVIDERS_IMGS_PATH . "/$chopID");
            } catch (\Exception $e) {
                $this->log->error("Can't scan service provider's dir: " . $e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }

            return count($dirList) - 2;
        }

        /** Удаляет файл из папки ЧОП */
        public function deleteFile($chopID, $fileName)
        {
            try {
                // ищем файл в директории и если находим - удаляем
                $dirList = scandir(SERVICE_PROVIDERS_IMGS_PATH . "/$chopID");
                foreach($dirList as $value) {
                    if (strpos($value, $fileName) !== false)
                        return unlink(SERVICE_PROVIDERS_IMGS_PATH . "/$chopID/$value");                    
                }
            } catch (Exception $e) {
                $this->log->error("Can't delete file $fileName.", ['METHOD' => __METHOD__]);
                return false;
            }
        }

        /** Сохранение данных ЧОП из вкладки Организация */
        public function saveOrganizationData($chopID, $inputs, $request)
        {
            $this->chopID = $chopID;
            $this->inputs = $inputs;
            try {
                $this->db->beginTransaction();

                $this->saveProviderData($request);
                $this->saveCitiesOffGeoData();
                $this->saveServicesData();
                
                // коммитим транзакцию
                if (!$this->db->commit()) throw new \Exception("Can't commit transaction");
                
                return true;
            } catch (\Exception $e) {
                $this->log->error($e->getMessage(), ['METHOD' => __METHOD__]);
                $this->db->rollBack();
                return false;
            }
        }

        /** Сохраняет данные сервис-провайдера в его таблицу */
        private function saveProviderData($request)
        {
            $query = 'UPDATE service_providers 
                        SET uid = MD5(:uid), request = IF (request, request, IF (NOT active, 1, :request)),
                        display_name = :displayName, phone = :phone, detail_data = :detailData, updated = DEFAULT
                        WHERE id_client = :chopID';
                
            // генерим уникальный идентификатор ЧОП UID (меняем старый)
            $uid = $this->chopID . $this->inputs['brand_name'];
            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':uid', $uid, \PDO::PARAM_STR);
                $stm->bindValue(':request', $request, \PDO::PARAM_INT);
                $stm->bindValue(':displayName', $this->inputs['brand_name'], \PDO::PARAM_STR);
                $stm->bindValue(':phone', $this->inputs['main_phone'], \PDO::PARAM_STR);
                $stm->bindValue(':detailData', json_encode($this->inputs), \PDO::PARAM_STR);
                $stm->bindValue(':chopID', $this->chopID, \PDO::PARAM_INT);
                $stm->execute();
            } catch (\PDOException $e) {
                $this->log->error($e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("saveProviderData error");
            }
        }

        /** Сохраняет данные городов */
        private function saveCitiesOffGeoData()
        {
            // вставляет в справочник городов новые города, если их нет. id_city вычисляем сами
            $query1 = 'INSERT IGNORE INTO cities (id_city, g_place_id, name, area, region, name_en, area_en, region_en, id_country)
                        VALUES ((SELECT * FROM (SELECT MAX(id_city) FROM cities) AS Max) + 1,
                        :gPlaceID, :name, :area, :region, :name_en, :area_en, :region_en,
                        (SELECT id_country FROM countries WHERE g_name = :gName AND public_name = :publicName))';
            
            // если город уже есть, то при наличии региона обновляем это поле
            $query11 = 'UPDATE cities SET region = :region, region_en = :region_en WHERE g_place_id = :gPlaceID';

            // добавляет новые соответствия ЧОП - Город
            $query2 = 'INSERT IGNORE INTO providers_cities_link (id_client, id_city) VALUES (:idClient, 
                        (SELECT id_city FROM cities WHERE g_place_id = :gPlaceID))';
            
            // добавляет геоданные
            $query3 = "INSERT IGNORE INTO offices_coords (id_client, latitude, longitude) VALUES (:idClient, :lat, :lng)";
             
            // формируем условие IN из списка g_place_id для запроса 4
            if (!($this->inputs['city_id'] ?? false)) {
                $query4InSelect = '0'; // нет id_city = 0;
            } else {
                $inGPlacesList = '';
                foreach($this->inputs['city_id'] as $cityID) {
                    $inGPlacesList .= "'$cityID', ";
                }
                $inGPlacesList = rtrim($inGPlacesList, ', ');
                $query4InSelect = "SELECT id_city FROM cities WHERE g_place_id IN ($inGPlacesList)";
            }            

            // удаляет старые соответствия из ЧОП - Город, которых уже нет в новом списке
            $query4 = "DELETE FROM providers_cities_link WHERE id_client = :idClient AND id_city NOT IN ($query4InSelect)";

            // формируем условие IN из списка latitude и longitude для запроса 5
            $inLatitudes = [];
            $inLongitudes = [];
            for($i = 0, $c = count($this->inputs['latitude']); $i < $c; $i++) {
                $inLatitudes[] = $this->inputs['latitude'][$i];
                $inLongitudes[] = $this->inputs['longitude'][$i];
            }
            if (count($inLatitudes) === 0) {
                $inLatitudes = 0;
                $inLongitudes = 0;
            } else {
                $inLatitudes = join(', ', $inLatitudes);
                $inLongitudes = join(', ', $inLongitudes);
            }            

            // удаляет старые соответствия ЧОП - координаты
            $query5 = "DELETE FROM offices_coords WHERE id_client = :idClient 
                        AND latitude NOT IN ($inLatitudes) AND longitude NOT IN ($inLongitudes)";

            try {
                $stm1 = $this->db->prepare($query1);
                $stm2 = $this->db->prepare($query2);
                $stm3 = $this->db->prepare($query3);
                $stm4 = $this->db->prepare($query4);
                $stm5 = $this->db->prepare($query5);

                // для каждого города ЧОП из входящего списка добавляем город в справочник городов и соответствие ЧОП
                for($i = 0, $c = count($this->inputs['city_id']); $i < $c; $i++) {
                    // разбираем поле city
                    $city = funcsLib::parseCityToArr($this->inputs['city'][$i]);
                    // формируем город на транслите
                    $name_en = funcsLib::RuToEnTranslit($city['city']);
                    $area_en = funcsLib::RuToEnTranslit($city['area']);
                    $region_en = funcsLib::RuToEnTranslit($city['region']);

                    // query 1
                    $stm1->bindValue(':gPlaceID', $this->inputs['city_id'][$i], \PDO::PARAM_STR);
                    $stm1->bindValue(':name', $city['city'], \PDO::PARAM_STR);
                    $stm1->bindValue(':area', $city['area'], \PDO::PARAM_STR);
                    $stm1->bindValue(':region', $city['region'], \PDO::PARAM_STR);
                    $stm1->bindValue(':name_en', $name_en, \PDO::PARAM_STR);
                    $stm1->bindValue(':area_en', $area_en, \PDO::PARAM_STR);
                    $stm1->bindValue(':region_en', $region_en, \PDO::PARAM_STR);
                    $stm1->bindValue(':gName', $this->inputs['country'], \PDO::PARAM_STR);
                    $stm1->bindValue(':publicName', $this->inputs['public_country'], \PDO::PARAM_STR);
                    $stm1->execute();

                    // query 11
                    // проверяем была ли вставка и если нет: если есть регион - обновляем это поле
                    if ($stm1->rowCount() === 0 && $city['region'] != '') {
                        $stm11 = $this->db->prepare($query11);
                        $stm11->bindValue(':region', $city['region'], \PDO::PARAM_STR);
                        $stm11->bindValue(':region_en', $region_en, \PDO::PARAM_STR);
                        $stm11->bindValue(':gPlaceID', $this->inputs['city_id'][$i], \PDO::PARAM_STR);
                        $stm11->execute();
                    }

                    // query 2
                    $stm2->bindValue(':idClient', $this->chopID, \PDO::PARAM_INT);
                    $stm2->bindValue(':gPlaceID', $this->inputs['city_id'][$i], \PDO::PARAM_STR);
                    $stm2->execute();

                    // query 3
                    $stm3->bindValue(':idClient', $this->chopID, \PDO::PARAM_INT);
                    $stm3->bindValue(':lat', $this->inputs['latitude'][$i], \PDO::PARAM_STR);
                    $stm3->bindValue(':lng', $this->inputs['longitude'][$i], \PDO::PARAM_STR);
                    $stm3->execute();
                }

                // query 4
                $stm4->bindValue(':idClient', $this->chopID, \PDO::PARAM_INT);
                $stm4->execute();
                // query 5
                $stm5->bindValue(':idClient', $this->chopID, \PDO::PARAM_INT);
                $stm5->execute();

            } catch (\PDOException $e) {
                $this->log->error($e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("saveCitiesOffGeoData error");
            }

        }

        /** Сохраняет данные ЧОП по оказываемым сервисам */
        private function saveServicesData()
        {
            // соответствие параметров услуг в форме и id_service
            $services = ['design_installation_check' => 1, 'pult_security_check' => 2, 'physic_security_check' => 3,
                        'cctv_check' => 4, 'gps_check' => 5, 'collection_check' => 6, 'access_control_check' => 7,
                        'service_check' => 8, 'wired_check' => 9, 'wireless_check' => 10, 'cargo_escort_check' => 11,
                        'gas_check' => 12, 'water_check' => 13];
            
            // добавляем новые соответствия услуг для id_client
            $query1 = "INSERT IGNORE INTO providers_services_link (id_client, id_service) VALUES (:idClient, :idService)";

            $idServiceList = [];
            try {
                $stm1 = $this->db->prepare($query1);

                foreach($services as $key => $idService) {
                    $val = $this->inputs[$key] ?? false;
                    if ($val == 'on') {
                        $stm1->bindValue(':idClient', $this->chopID, \PDO::PARAM_INT);
                        $stm1->bindValue(':idService', $idService, \PDO::PARAM_INT);
                        $stm1->execute();

                        // формируем serviceList для query 2
                        $idServiceList[] = $idService;
                    }
                }

                // проверяем введены ли сервисы
                if (count($idServiceList) === 0) $idServiceList = 0; // 0 - отсутствующий id
                else $idServiceList = join(', ', $idServiceList);
                
                // удаляем старые соответствия услуг
                $query2 = "DELETE FROM providers_services_link WHERE id_client = :idClient AND id_service NOT IN ($idServiceList)";

                $stm2 = $this->db->prepare($query2);
                $stm2->bindValue(':idClient', $this->chopID, \PDO::PARAM_INT);
                $stm2->execute();

            } catch (\PDOException $e) {
                $this->log->error($e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("saveServicesData error");
            }
        }

        /** Сохраняет прейскурант в БД */
        public function savePriceData($chopID, $inputs)
        {
            $query = "UPDATE service_providers SET price = :price, updated = default WHERE id_client = :idClient";

            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':price', json_encode($inputs), \PDO::PARAM_STR);
                $stm->bindValue(':idClient', $chopID, \PDO::PARAM_INT);
                $stm->execute();

                return true;
            } catch (\PDOException $e) {
                $this->log->error($e->getMessage(), ['METHOD' => __METHOD__]);
                return false;
            }
        }

        /** Возвращает прейскурант в JSON */
        public function getPriceDataByUser($userID)
        {
            $query = "SELECT price FROM service_providers JOIN users_providers_link USING (id_client) WHERE id_user = :idUser";
            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':idUser', $userID, \PDO::PARAM_INT);
                $stm->execute();

                return $stm->fetchObject();
            } catch (\PDOException $e) {
                $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("Can't read price data");
            }
        }

        /** Возвращает данные ЧОП в JSON и id_client */
        public function getDetailDataByUser($userID)
        {
            $query = "SELECT id_client, uid, detail_data FROM service_providers JOIN users_providers_link 
                        USING (id_client) WHERE id_user = :idUser";
            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':idUser', $userID, \PDO::PARAM_INT);
                $stm->execute();

                return $stm->fetchObject();
            } catch (\PDOException $e) {
                $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("Can't read detail data");
            }
        }

        /** Возвращает данные detail_data ЧОП по uid  */
        public function getAgencyData($uid)
        {
            $query = "SELECT id_client, detail_data FROM service_providers WHERE uid = :uid";

            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':uid', $uid, \PDO::PARAM_STR);
                $stm->execute();

                return $stm->fetchObject();
            } catch (\PDOException $e) {
                $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("Can't read detail data");
            }
        }

        /** Возвращает список всех ЧОП в локации */
        public function getProvidersOffersAll($whereArr, $page, $quantity = false, $providersByPage = false)
        {
            // если показываем загруженные ЧОПы
            $tempChopQuery = '';
            $tempChopQueryCount = '';
            if (USE_TEMP_CLIENTS) {
                $tempChopQuery = "UNION SELECT temp_id_client, uid, name, phone, city, address
                                    FROM temp_clients WHERE city = :city AND active = 1";
                $tempChopQueryCount = "UNION SELECT COUNT(temp_id_client) AS q FROM temp_clients WHERE city = :city AND active = 1";
            }
            
            $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT id_client, uid, display_name AS name, phone, detail_data AS data, price
                        FROM service_providers
                        JOIN providers_cities_link USING (id_client) JOIN cities USING (id_city)
                        JOIN providers_services_link USING (id_client) JOIN services USING (id_service)
                        WHERE active = 1 AND cities.name = :city
                        $tempChopQuery LIMIT :from,:onPage";

            // запрос, чтобы узнать количество строк (если не известно)
            $queryCount = "SELECT SUM(q) FROM ((SELECT COUNT(id_client) AS q FROM service_providers
                            JOIN providers_cities_link USING (id_client) JOIN cities USING (id_city) 
                            WHERE active = 1 AND cities.name = :city $tempChopQueryCount) AS tbl)";
                            // JOIN providers_services_link USING (id_client) JOIN services USING (id_service)

            // если знаем количество строк на странице
            if ($providersByPage) {
                $from = $providersByPage * $page - $providersByPage;
                $onPage = $providersByPage;
            // если первая страница - просто выводим максимум, а потом рассчитаем сколько надо
            } elseif ($page === 1) {
                $from = 0;
                $onPage = 20; // максимальное кол-во на странице
            // если не первая страница - надо в начале рассчитать количество на странице
            } else {
                // получаем количество всего
                try {
                    $stm = $this->db->prepare($queryCount);
                    $stm->bindValue(':city', $whereArr['city'], \PDO::PARAM_STR);
                    $stm->execute();

                    $quantity = $stm->fetchColumn();
                } catch (\PDOException $e) {
                    $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                    throw new SPDMException("Can't read provider's data (calc rows quantity)");
                } catch (\ErrorException $e) {
                    $this->log->error($e);
                }
                // рассчитываем страницы
                $providersByPage = funcsLib::calcProvidersByPage($quantity);
                $from = $providersByPage * $page - $providersByPage;
                $onPage = $providersByPage;
            }

            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':city', $whereArr['city'], \PDO::PARAM_STR);
                $stm->bindValue(':from', $from, \PDO::PARAM_INT);
                $stm->bindValue(':onPage', $onPage, \PDO::PARAM_INT);
                     
                $stm->execute();

                if (!$quantity) $quantity = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

                return (object)[
                    'quantity' => $quantity,
                    'providers' => $stm->fetchAll(\PDO::FETCH_OBJ)
                ];
            } catch (\PDOException $e) {
                $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("Can't read provider's data");
            } catch (\ErrorException $e) {
                $this->log->error($e);
            }
        }

        /** Возвращает список ЧОП с детальными данными по переданному условию */
        public function getProvidersOffersByConditions($whereArr, $services, $optServices, $page, $quantity = false, $providersByPage = false)
        {
            // если показываем загруженные ЧОПы
            $tempChopQuery = '';
            $tempChopQueryCount = '';
            if (USE_TEMP_CLIENTS) {
                $tempChopQuery = "UNION SELECT temp_id_client, uid, name, phone, city, address
                                            FROM temp_clients WHERE city = :city AND active = 1";
                $tempChopQueryCount = "UNION SELECT COUNT(temp_id_client) AS q FROM temp_clients WHERE city = :city AND active = 1";
            }

            // если показываем по геолокации
            define('USE_NEAR', true);
            $nearQuery1 = '';
            $nearQuery2 = '';
            if (USE_NEAR) {
                // сколько километров от центра квадрата в котором ищем
                define('SQUARE_SIDE', 10);

                if (isset($whereArr['latitude'])) {
                    // параметр, что учитываем локацию для подстановки в параметры bindValue
                    $USE_NEAR = true;
                    
                    $lat = $whereArr['latitude'];
                    $lng = $whereArr['longitude'];
                    // сколько широта и долгота в километрах
                    $latKm = 111.111;
                    $lngKm = $latKm*cos(((float)$lat)*pi()/180);

                    // формируем доп запросы по геокоординатам
                    $ss = SQUARE_SIDE;
                    $nearQuery1 = "JOIN offices_coords USING (id_client)";
                    $nearQuery2 = "OR (ABS(latitude - :lat) * $latKm < $ss AND ABS(longitude - :lng) * $lngKm < $ss)";
                }
            }            

            // формируем список сервисов для условия IN
            $servicesStr = "'" . join("','", array_values($services)) . "'";
            $optServicesStr = "'" . join("','", array_values($optServices)) . "'";

            // если есть опциональные сервисы
            $optServicesCount = count($optServices);
            $optionsQuery = '';
            if ($optServicesCount > 0) {
                // JOIN таблица с обязательным совпадением всех опций map_name IN
                $optionsQuery = "JOIN (SELECT id_client, COUNT(id_client) AS count
                                    FROM providers_services_link JOIN services USING (id_service)
                                    WHERE map_name IN ($optServicesStr) GROUP BY (id_client) HAVING count = $optServicesCount)
                                    AS srv2 USING (id_client)";
            }            
            // Выбираем ЧОПЫ по требуемым сервисам map_name IN,
            // если есть доп опции, дополнительно выбираем по опциональным сервисам optionsQuery
            $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT id_client, uid, display_name AS name, phone, detail_data AS data, price
                        FROM service_providers
                        JOIN providers_cities_link USING (id_client) JOIN cities USING (id_city)
                        JOIN providers_services_link USING (id_client) JOIN services USING (id_service) $nearQuery1
                        $optionsQuery
                        WHERE active = 1 AND map_name IN ($servicesStr) AND
                        ((cities.name = :city AND cities.region LIKE :region AND cities.area LIKE :area) $nearQuery2)
                        $tempChopQuery LIMIT :from,:onPage";

            // запрос, чтобы узнать количество строк (если не известно)
            $queryCount = "SELECT SUM(q) FROM ((SELECT COUNT(id_client) AS q FROM service_providers
                            JOIN providers_cities_link USING (id_client) JOIN cities USING (id_city)
                            JOIN providers_services_link USING (id_client) JOIN services USING (id_service) $optionsQuery
                            WHERE active = 1 AND map_name IN ($servicesStr) AND
                            (cities.name = :city AND cities.region LIKE :region AND cities.area LIKE :area)
                            $tempChopQueryCount) AS tbl)";

            // если знаем количество строк на странице
            if ($providersByPage) {
                $from = $providersByPage * $page - $providersByPage;
                $onPage = $providersByPage;
            // если первая страница - просто выводим максимум, а потом рассчитаем сколько надо
            } elseif ($page === 1) {
                $from = 0;
                $onPage = 20; // максимальное кол-во на странице
            // если не первая страница - надо в начале рассчитать количество на странице
            } else {
                // получаем количество всего
                try {
                    $stm = $this->db->prepare($queryCount);
                    $stm->bindValue(':city', $whereArr['city'], \PDO::PARAM_STR);
                    $stm->bindValue(':region', "%{$whereArr['region']}%", \PDO::PARAM_STR);
                    $stm->bindValue(':area', "%{$whereArr['area']}%", \PDO::PARAM_STR); 
                    $stm->execute();
    
                    $quantity = $stm->fetchColumn();
                } catch (\PDOException $e) {
                    $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                    throw new SPDMException("Can't read provider's data (calc rows quantity)");
                } catch (\ErrorException $e) {
                    $this->log->error($e);
                }
                // рассчитываем страницы
                $providersByPage = funcsLib::calcProvidersByPage($quantity);
                $from = $providersByPage * $page - $providersByPage;
                $onPage = $providersByPage;
            }
            // запрос результатов поиска
            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':city', $whereArr['city'], \PDO::PARAM_STR);
                $stm->bindValue(':region', "%{$whereArr['region']}%", \PDO::PARAM_STR);
                $stm->bindValue(':area', "%{$whereArr['area']}%", \PDO::PARAM_STR);
                $stm->bindValue(':from', $from, \PDO::PARAM_INT);
                $stm->bindValue(':onPage', $onPage, \PDO::PARAM_INT);
                if ($USE_NEAR ?? false) {
                    $stm->bindValue(':lat', $lat, \PDO::PARAM_STR);
                    $stm->bindValue(':lng', $lng, \PDO::PARAM_STR);
                }                
                $stm->execute();

                if (!$quantity) $quantity = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();                

                return (object)[
                    'quantity' => $quantity,
                    'providers' => $stm->fetchAll(\PDO::FETCH_OBJ)
                ];
            } catch (\PDOException $e) {
                $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("Can't read provider's data");
            } catch (\ErrorException $e) {
                $this->log->error($e);
            }
        }

        /** Возвращает uid и updated всех ЧОПов */
        public function getAllProviders($onlyActive = false)
        {
            // если нужны только активные ЧОПы
            $where = '';
            if ($onlyActive) $where = ' WHERE active = 1';

            $query = "SELECT uid, DATE(updated) as updated FROM service_providers" . $where;
            try {
                foreach ($this->db->query($query, \PDO::FETCH_OBJ) as $providerObj) yield $providerObj;
            } catch (\PDOException $e) {
                $this->log->error('Error in SQL: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("Can't read full service providers list");
            }
        }

        /** Возвращает запросы на модерацию страниц ЧОП */
        public function getProviderRequests($limit = 1)
        {
            $query = "SELECT SQL_CALC_FOUND_ROWS uid as id, display_name as name, updated, cities.name as city, 
                        users.name as userName, users.patronymic as userPatronymic, email
                        FROM service_providers JOIN providers_cities_link USING (id_client) JOIN cities USING (id_city)
                        JOIN users_providers_link USING (id_client) JOIN users USING (id_user)
                        WHERE request = 1 ORDER BY updated LIMIT :limit";
            
            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':limit', $limit, \PDO::PARAM_INT);
                $stm->execute();

                $count = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

                return [
                    'count' => $count,
                    'requests' => $stm->fetchAll(\PDO::FETCH_ASSOC)
                ];
            } catch (\PDOException $e) {
                $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("Can't read provider moderation requests data");
            } catch (\ErrorException $e) {
                $this->log->error($e);
            }
        }

        /** Утверждает модерацию страницы ЧОП */
        public function approveProviderRequest($uid)
        {
            $query = "UPDATE service_providers SET request = 0, active = 1 WHERE uid = :uid";

            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':uid', $uid, \PDO::PARAM_STR);

                return $stm->execute();
            } catch (\PDOException $e) {
                $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("Can't read provider moderation requests data");
            } catch (\ErrorException $e) {
                $this->log->error($e);
            }
        }

        /** Отклоняет модерацию страницы ЧОП */
        public function declineProviderRequest($uid)
        {
            $query = "UPDATE service_providers SET request = 0, active = 0 WHERE uid = :uid";

            try {
                $stm = $this->db->prepare($query);
                $stm->bindValue(':uid', $uid, \PDO::PARAM_STR);

                return $stm->execute();
            } catch (\PDOException $e) {
                $this->log->error('SQL Error: ' . $e->getMessage(), ['METHOD' => __METHOD__]);
                throw new SPDMException("Can't read provider moderation requests data");
            } catch (\ErrorException $e) {
                $this->log->error($e);
            }
        }

    }
