<?php ## инициирует подключение к СУБД MySQL

    $log = \engines\LogEngine::create();
    try {
        $host = HOST_NAME;
        $dbname = DB_NAME;
        $user = DB_USER_NAME;
        $password = DB_USER_PASSWORD;
        $db_connect = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=UTF8",
            $user, 
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => true
            ]
        );
    }
    catch (PDOException $e) {
        // записываем в лог
        $log->error("Can't connect to MySQL: Host - $host; Database - $dbname; User - $user. Error: " . $e->getMessage());
        exit();
    }
    