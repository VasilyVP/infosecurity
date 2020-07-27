<?php # Сервис создания файлов Sitemap
    use exceptions\MDMException, exceptions\SPDMException;
    use models\AddressesDataModel, models\ServiceProvidersDataModel;
    use engines\SEOEngine;

    $start = $_SERVER['REQUEST_TIME_FLOAT'];

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // ответ по умолчанию
    $response = [
        'code' => 0,
        'data' => null,
        'message' => 'Unexpected error',
    ];

    // фильтруем входные параметры
    $args = [
        'mapName' => ['filter' => FILTER_SANITIZE_STRING],
        'token' => ['filter' => FILTER_DEFAULT], // не фильтруем
    ];
    // токен для проверки прав запуска скрипта
    const TOKEN = '405bd85d2652a56fab46c17de4e27a14';

    $inputs = filter_input_array(INPUT_GET, $args);

    // скрипт запускается либо по передаче валидного токена либо при авторизации администратором
    if ($inputs['token'] !== TOKEN) {
        $auth = \engines\AuthenticationEngine::create();

        // проверяем авторизацию и роль пользователя (admin)
        if (!($auth->isAuthenticated() && $auth->getUserRole() === 'admin')) {
            $response['message'] = 'Unauthorized access';
            echo json_encode($response);
            exit();
        }        
    }    
    
    // подключаем SEOEngine
    $seo = new SEOEngine;

    // счетчик числа записей
    $count = 0;

    # если формируем sitemapcities.xml
    if ($inputs['mapName'] == 'cities') {
        // получаем города
        try {
            $adm = AddressesDataModel::create();
            
            // получаем первых 100 городов по кол-ву ЧОПов и формируем ссылки на search_page
            foreach ($adm->getTopCities(SEO_TOP_CITIES) as $cityObj) {
                $countryEn = mb_strtolower($cityObj->countryEn);
                $cityEn = mb_strtolower($cityObj->cityEn);
                $link = "https://" . DOMAIN_NAME . "/search/$countryEn/$cityEn?service-type=all";
                // добавляем запись в Sitemap
                $seo->addSitemapRecord($link, date('Y-m-d'), 'monthly', 0.4);

                $count++;
            }
            // формируем строку Sitemap
            $sitemap = $seo->getSitemapStr();
            // определяем название файла Sitemap
            $sitemapFile = SITEMAP_FILES['cities'];
        } catch (MDMException $e) {
            $log->error($e->getMessage(), ['METHOD' => __FILE__]);
        }

    # если формируем Sitemap по страницам ЧОП
    } elseif ($inputs['mapName'] == 'providers') {
        try {
            $spdm = ServiceProvidersDataModel::create();

            foreach ($spdm->getAllProviders(true) as $providerObj) {
                $link = 'https://' . DOMAIN_NAME . "/agency/$providerObj->uid";
                // добавляем запись в Sitemap
                $seo->addSitemapRecord($link, $providerObj->updated, 'monthly', 0.2);

                $count++;
            }
            // формируем строку Sitemap
            $sitemap = $seo->getSitemapStr();
            // определяем название файла Sitemap
            $sitemapFile = SITEMAP_FILES['providers'];

            // сжимаем файл
            $sitemap = gzencode($sitemap);
        } catch (SPDMException $e) {
            $log->error($e->getMessage(), ['METHOD' => __FILE__]);
        }

    # если формируем Sitemap по поисковым запросам
    } elseif ($inputs['mapName'] == 'queries') {
        $src = fopen(QUERIES_SITEMAP_SOURCE, 'r');
        if ($src !== false) {

            // получаем список ТОП городов и стран из БД
            $adm = AddressesDataModel::create();

            // формируем массив городов замены: по ТОП из БД + ТОП из списка конфигурации
            $cities = [];
            foreach ($adm->getTopCities(SEO_TOP_CITIES) as $cityObj) {
                $cities[strtolower($cityObj->cityEn)] = strtolower($cityObj->countryEn);
            }
            $cities = array_merge($cities, SEO_ADD_CITIES);

            // делаем записи для каждой ссылки из файла ссылок по запросам
            while (($data = fgetcsv($src, 1000, ';')) !== false) {
                // пропускаем заголовки в csv  
                if ($data[1] == 'Link') continue;

                // делаем запись в сайтмэп для каждого города
                foreach ($cities as $city => $country) {
                    // для каждой строки url заменяем страну и город в адресе на требуемые
                    $url = preg_replace('/^(.+search\/)\w+\/\w+(\?.*)$/', "$1$country/$city$2", $data[1]);

                    // добавляем строчку в сайтмэп
                    $seo->addSitemapRecord($url, date('Y-m-d'), 'monthly', 0.6);

                    $count++;
                }                
            }
            fclose($src);
            // формируем строку Sitemap
            $sitemap = $seo->getSitemapStr();

            // сжимаем файл
            $sitemap = gzencode($sitemap);
            // определяем название файла Sitemap
            $sitemapFile = SITEMAP_FILES['queries'];
        }
    }

    // записываем в файл
    $result = file_put_contents($sitemapFile, $sitemap);
    
    $end = microtime(true);
    $duration = $end - $start;

    if ($result === false) {
        $log->error("Can't write <{$inputs['mapName']}> sitemap file", ['METHOD' => __FILE__]);
    } else {
        $log->info("Created SiteMap <{$inputs['mapName']}>. $count items. Duration: $duration sec.", ['METHOD' => __FILE__]); //at $endTime
    }    

    //$endTime = date(DATE_RFC822);
