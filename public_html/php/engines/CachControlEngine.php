<?php /** Механизм управления кэшированием */
    namespace engines;

    class CachControlEngine
    {
        /** инициирует все входные переменные
         * $rout - страница из массива ROUTS
         * */ 
        public function __construct($rout)
        {
            // проверяем, описано ли кэширование для страницы
            $caching = $rout['caching'] ?? false;
            if (!$caching) return;

            // если авторизация есть - кэширование не делаем
            //$auth = \engines\AuthenticationEngine::create();

            //if ($auth->isAuthenticated()) $cachHeader = 'must-revalidate';
            //else
            
            $cachHeader = join(', ', $caching);
            
            //$log = LogEngine::create();
            
            $cachHeader = 'Cache-Control: ' . $cachHeader;
            header($cachHeader);
        }

    }
