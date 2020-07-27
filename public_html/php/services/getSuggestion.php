<?php # сервис получения подсказок городов с Google
    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // определяет тип запроса: соответствие параметра запроса require и поля G - types
    $requestTypes = [
        'city' => '(cities)',
        'address' => 'address',
        'organization' => 'establishment'
    ];
    
    // получаем входящий запрос на дополнения
    $input = $_GET['input'] ?? false; // надо сделать проверку текста и авторизацию!!!
    $country = $_GET['country'] ?? false;
    $require = $_GET['require'] ?? false;
    $session_token = $_GET['session_token'] ?? false;
    // определяет где искать (G location)
    $locationArr = $_GET['location'] ?? false;

    // формируем преобразование require -> types
    $request = $requestTypes[$require];

    // параметры Google Places API
    $placesApiUrl = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
    $params = [
      'types' => $request,
      'input' => $input,
      'language' => 'ru', // должно быть на входе!!!
      'components' => 'country:' . $country,
      ///'key' => XXX
    ];
    // если location не пустой, то добавляем параметры location и radius для сужения вариантов
    if ($locationArr['latitude']) {
        // формируем location для G
        $location = $locationArr['latitude'] . ',' . $locationArr['longitude'];
        // добавляем параметры в запрос
        $params['location'] = $location;
        $params['radius'] = '20000'; // в метрах
    }
    // если есть session_token - добавляем в запрос
    if ($session_token) $params['session_token'] = $session_token;

    // формируем строку запроса
    $query = $placesApiUrl . '?' . http_build_query($params);

    // формирует запрос
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);

    // выводим JSON c предложениями
    echo $response;
