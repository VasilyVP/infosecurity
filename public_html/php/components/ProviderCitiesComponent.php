<?php # компонент вывода списка топ городов для поиска по ним

    namespace components;

    use models\AddressesDataModel;
    use exceptions\MDMException;

    class ProviderCitiesComponent
    {
        private $log;

        public function __construct()
        {
            $this->log = \engines\LogEngine::create();
        }

        public function getCitiesLiList()
        {
            try {
                $adm = AddressesDataModel::create();

                $list = [];
                foreach($adm->getTopCities(80) as $cityObj) {
                    $countryEn = mb_strtolower($cityObj->countryEn);
                    $cityEn = mb_strtolower($cityObj->cityEn);
                    // меняем пробел на '_'
                    $countryEn = str_replace(' ', '_', $countryEn);
                    $cityEn = str_replace(' ', '_', $cityEn);

                    $list[] = "<li><a name='cityList' class='text-muted' 
                        href='/search/$countryEn/$cityEn?service-type=all&from=citiesList&page=1' title='$cityObj->city'>$cityObj->city</a></li>";
                        // rel='nofollow'
                }
                echo join('', $list);
            } catch (MDMException $e) {
                $this->log->warning($e->getMessage(), ['METHOD' => __FILE__]);
            }
        }

    }
