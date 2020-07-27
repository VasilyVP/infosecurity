<?php # сервис получения данных с сервера reCAPCHA
    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';
    
    $token = $_POST['token'] ?? false;
    // проверяем токен
    if (!$token) {
        $log->warning('Empty token', ['METHOD' => __METHOD__]);
        exit();
    }

    // формируем строку запроса
    $query = PATHS['reCAPCHAsiteVerify'];

    // формирует запрос
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $query,
        CURLOPT_RETURNTRANSFER => 1,
     //   CURLOPT_PROXYPORT => 3128,
        //CURLOPT_TIMEOUT => 2,
        CURLOPT_POSTFIELDS => [
            'secret' => CAPCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]
    ]);
    /*
    curl_setopt($ch, CURLOPT_URL, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'secret' => CAPCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]);
    */

    $response = curl_exec($ch);
    curl_close($ch);

  //  $log->debug('No response');
    //setcookie('CAPTCHA', 'ok');
    echo $response;
