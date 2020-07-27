<?php # Файл общих обязательный подключений
    // подключаем конфигурацию и пути
    require_once $_SERVER['DOCUMENT_ROOT'] . '/php/config.php';

    // автозагрузка Composer
    require 'vendor/autoload.php';

    // подключаем автозагрузку классов
    spl_autoload_register(function($classname)
        {
            $script = str_replace('\\', '/', $classname) . '.php'; // нужно для Linux
            if (file_exists(PHP_SCRIPTS_PATH . $script))
                require_once PHP_SCRIPTS_PATH . $script;
        }
    );
    
    // инициализируем ведение логов, если включено
    $log = \engines\LogEngine::create();

