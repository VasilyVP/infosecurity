<?php # компонент определения города по IP адресу
    namespace components;

    class LocationByIPComponent
    {
        private $country;
        private $region;
        private $area;
        private $city;
        private $settlement;
        private $dadataApiUrl = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/detectAddressByIp';
       // private $dadataApiToken = XXX;

        private $log;

        public function __construct()
        {
            $this->log = \engines\LogEngine::create();

            $locationD = $this->getLocationData();
            if (!$locationD) return;
            $this->country = $locationD->country ?? null;
            $this->region = $locationD->region_with_type ?? null;
            $this->area = $locationD->area_with_type ?? null;
            $this->city = $locationD->city ?? null;
            $this->settlement = $locationD->settlement ?? null;

            // заменяем "обл" на "область"
            $this->region = str_replace('обл', 'область', $this->region);
        }

        private function getLocationData()
        {
            $ip = $_SERVER['REMOTE_ADDR'];

            $params = [
                'ip' => $ip,
            ];
            // формируем строку запроса
            $query = $this->dadataApiUrl . '?' . http_build_query($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $query);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                "Authorization: Token $this->dadataApiToken",
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            curl_close($ch);

            $responseObj = json_decode($response);

            if (!isset($responseObj->location->data)) return false;
            else return $responseObj->location->data;
        }

        public function getCountry()
        {
            return $this->country ? $this->country : '';
        }

        public function getLocality()
        {
            $locality = [];
    
            if ($this->city) $locality[] = $this->city;
            if ($this->settlement) $locality[] = $this->settlement;
            if ($this->area) $locality[] = $this->area;  
            if ($this->region && !strpos($this->region, $this->city)) $locality[] = $this->region;
        
            $locality = join($locality, ', ');

            return $locality;
        }

        public function getCity()
        {
            $city = [];
    
            if ($this->city) $city[] = $this->city;
            if ($this->settlement) $city[] = $this->settlement;
            $city = join($city, ', ');

            return $city;
        }

        public function getArea()
        {
            return $this->area;
        }
        
        public function getRegion()
        {
            return $this->region;
        }

    }
