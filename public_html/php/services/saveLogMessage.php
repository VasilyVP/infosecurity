<?php ## Сервис сохранения сообщения в лог scanox. Используется совместно с cli утилитами

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // токен для проверки прав запуска скрипта
    const TOKEN = '405bd85d2652a56fab46c17de4e27a14';

    // проверяем токен
    if ($_GET['token'] != TOKEN) exit();

    // сообщение
    $msg = $_GET['message'];

    // логируем запись
    $log->info($msg, ['METHOD' => __FILE__]);
    