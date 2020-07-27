<?php

    use GuzzleHttp\Client;
    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    $response = 'No response';

    $client = new Client([
        'base_uri' => 'https://development.scanox.pro', //render?url=
        //'base_uri' => 'https://development.scanox.pro/search/rossiya/podolsk?service-type=signaling&connect-type=wired&signaling-place-type=flat&sec_signaling&nav-phys&page=1', // http://localhost:3000/render?url=
        'verify' => false,
        'headers' => [
            'User-Agent' => 'Scanox cache initiator'
        ]
    ]);

    try {
        //$response = $client->request('GET', '', ['auth' => ['Robin', 'vinnipuh1%']]);
        $response = $client->get('');
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
 
    echo $response->getBody();

    //phpinfo();