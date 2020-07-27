<?php /** Компонент определения локации (Страна и Город) по URL запроса из БД (по транслиту возвращает Название на русском) */
    namespace components;
    use engines\RoutingEngine, engines\LogEngine;
    use models\AddressesDataModel;

    class LocationByURLComponent
    {
        private $country;
        private $addrFull;

        public function __construct()
        {
            //$log = LogEngine::create();
            
            $re = RoutingEngine::create();
            // инициируем разбор адреса из RoutingEngine на компоненты country, city и т.д.
            $addrComp = $re->parseAddressURL();

            // проверяем с какой страницы пришли (если с main_page, то информация есть в sessionStorage)
            if (($re->getQueryArr()['from'] ?? false) == 'main') return;

            $adm = AddressesDataModel::create();
            // заполняем Страну и адрес из БД по запросу адресной строки
            $this->country = $adm->getCountry($addrComp['country']);
            $this->addrFull = $adm->getFullAddress($addrComp);
        }

        public function getFullAddress()
        {
            return $this->addrFull;
        }

        public function getCountry()
        {
            return $this->country;
        }

    }
