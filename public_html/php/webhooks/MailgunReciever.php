<?php # обработчик Webhook Mailgun
    
    use engines\MailgunEngine;
    use exceptions\MailgunEngineException;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // получаем входные данные (PHP автоматом не парсит application/json данные)
    $postData = file_get_contents('php://input');
    $data = json_decode($postData);

    if (!\engines\MailgunEngine::webhookVerify($data->signature)) {
        $log->warning('Incoming JSON is invalid', ['METHOD' => __FILE__]);
        // негативный ответ
        $status = [
            'code' => 400,
            'message' => 'Invalid json'
        ];
        echo json_encode($status);
        exit();
    }

    $event = $data->{'event-data'};
    $message[] = $event->recipient;
    $message[] = $event->event ?? '';
    $message[] = $event->reason ?? '';
    $message[] = $event->{'delivery-status'}->description ?? '';

    // проверяем тип события
    $eventType = $event->event ?? false;
    if ($eventType == 'clicked') $csvFile = CLICKED_EMAILS;
    elseif ($eventType == 'opened') $csvFile = OPENED_EMAILS;
    else $csvFile = FAILED_EMAILS;

    // открываем файл
    @$csv = fopen($csvFile, 'ab');
    if (!$csv) {
        $log->error("Error with $csvFile opening or creating", ['METHOD' => __FILE__]);
        $status = [
            'code' => 0,
            'message' => 'Internal error'
        ];
        echo json_encode($status);
        exit();
    }
    // записываем строку в csv
    @$res = fputcsv($csv, $message, ';');
    if ($res === false) {
        $log->error("Error with $csvFile writing", ['METHOD' => __FILE__]);
    }
    fclose($csv);

    // положительный ответ
    $status = [
        'code' => 200,
        'message' => 'Ok'
    ];
    echo json_encode($status);
