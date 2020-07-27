<?php # Секция вывода сервисных страниц. По URL определяет требуемую страницу и ее вставляет.
    use engines\RoutingEngine;

    $rout = RoutingEngine::create();

    $page = $rout->getRoutArr()[1];

    // подгружаем мэпинг страниц
    require SERVICES_PAGE . '/pages_config.php';

    // если странице есть - вставляем ее
    if ($page && (SERVICES_PAGES_MAP[$page] ?? false)) {
        require SERVICES_SECTIONS_PAGES . SERVICES_PAGES_MAP[$page];        
    } else {
        // страница не найдена
        header('HTTP/1.0 404 Not Found');
        // редирект на главную страницу
        header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']);
        exit();
    }    
    