<?php # Секция статей и документации. По URL определяет требуемую статью и ее вставляет.
    use engines\RoutingEngine;

    $rout = RoutingEngine::create();

    $page = $rout->getRoutArr()[1] ?? false;

    // подгружаем мэпинг страниц
    require ARTICLES_PAGE . '/pages_config.php';

    // если странице есть - вставляем ее
    if ($page && (ARTICLES_PAGES_MAP[$page] ?? false)) {
        require ARTICLES_PAGE_SECTIONS . '/articles_pages/' . ARTICLES_PAGES_MAP[$page];        
    } else {
        // страница не найдена
        header('HTTP/1.0 404 Not Found');
        // редирект на главную страницу
        header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']);
        exit();
    }    
    