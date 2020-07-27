<?php ## главный стартовый скрипт сайта    
    // подключаем обязательные пререквизиты: конфигурацию и автозагрузку классов
    require_once 'mandatory.php';
    // подключаем пути страниц и шаблонов
    require_once TEMPLATES_CFG;
    // подключаем пути к файлам бандлов или точек сбора JS скриптов
    require_once JS_ENTRY_PATHS_CFG;

    // инициируем аутентификацию
    $auth = \engines\AuthenticationEngine::create();

    // инициируем маршрутизацию
    $routing = \engines\RoutingEngine::create();
    
    // включаем буферизацию вывода
    ob_start();
    // загружаем шаблон
    require $routing->getPage();
    // запоминаем шаблон из буфера вывода
    $page = ob_get_contents();
    
    // завершаем буферизацию и очищаем буфер
    ob_end_clean();

    // заполняем шаблон
    $mainPage = new \engines\TemplateProcessing();
    
    $mainPage->setValues(
        [
            'LANGUAGE' => 'ru',
            'TITLE' => 'Установка охранной, пожарной сигнализации, видеонаблюдение на Scanox.pro',
            'DESCRIPTION' => 'Scanox: Предложения и цены на охранные услуги в вашем городе. Охрана домов, квартир, офисов. Системы видеонаблюдения.  Установка и обслуживание охранно-пожарной сигнализации. Физическая охрана объектов. Проектирование и монтаж ОПС. GPS мониторинг транспорта.',
            // подставляем type="module" для девелопмента
            'TYPE_MODULE' => TYPE_MODULE,
            // заполняем адреса JS скриптов на страницах
            //'JS_CONFIG' => JS_ENTRY_PATHS['config'],
            'JS_MAIN_PAGE' => JS_ENTRY_PATHS['main_page'],
            'JS_SEARCH_PAGE' => JS_ENTRY_PATHS['search_page'],
            'JS_CHOP_PAGE' => JS_ENTRY_PATHS['chop_page'],
            'JS_CHOP_PA_PAGE' => JS_ENTRY_PATHS['chop_pa_page'],
            'JS_LOGIN_SECTION' => JS_ENTRY_PATHS['login_section'],
            'JS_REGISTRATION_SECTION' => JS_ENTRY_PATHS['registration_section'],
            'JS_USER_TAB' => JS_ENTRY_PATHS['user_tab'],
            'JS_CONTACT_PAGE' => JS_ENTRY_PATHS['contact_page']
        ]
    );
        
    $mainPage->parseTemplate($page);

    // выводим страницу
    echo $mainPage->getPage();
