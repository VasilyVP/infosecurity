<?php # Конфигурация путей к файлам бандлов или точек сбора JS скриптов (для вариантов разработки и итоговой сборки)

    if (EXECUTION_AT == 'localhost' || EXECUTION_AT == 'development') {
        define('JS_ENTRY_PATHS', 
            [
                //'config' => '/js/config.js',
                'main_page' => '/templates/pages/main_page/js/main_page.js',
                'search_page' => '/templates/pages/search_page/js/search_page.js',
                'chop_page' => '/templates/pages/chop_page/js/chop_page.js',
                'chop_pa_page' => '/templates/pages/chop_pa_page/js/tabs.js',
                'login_section' => '/templates/common_sections/nav_section/js/login.js',
                'registration_section' => '/templates/common_sections/nav_section/js/registration.js',
                'user_tab' => '/templates/common_sections/user_section/js/user_tab.js',
                'contact_page' => '/templates/pages/contact_page/js/contact_page.js'
            ]);
    } else {
        define('JS_ENTRY_PATHS', 
            [
                //'config' => '/js/bundles/config.bundle.js',
                'main_page' => '/js/bundles/main_page.bundle.js',
                'search_page' => '/js/bundles/search_page.bundle.js',
                'chop_page' => '/js/bundles/chop_page.bundle.js',
                'chop_pa_page' => '/js/bundles/chop_pa_page.bundle.js',
                'login_section' => '/js/bundles/login_section.bundle.js',
                'registration_section' => '/js/bundles/registration_section.bundle.js',
                'user_tab' => '/js/bundles/user_tab.bundle.js',
                'contact_page' => '/js/bundles/contact_page.bundle.js'
            ]);
    }
