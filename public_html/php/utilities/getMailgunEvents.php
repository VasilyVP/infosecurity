<?php ## Скрипт получения событий из Mailgun

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    //use Mailgun\Mailgun;
    //use engines\DBConnection;
    use engines\MailgunEngine;
    use exceptions\MailgunEngineException;
    //use exceptions\DBException;

    $msg = 'Init';
    
    try {

        $mge = new MailgunEngine;

        
        $msg = $mge->getEvents([
            //'limit' => 10,
            'event' => 'failed',
            'severity' => 'permanent',
            'tags' => 'first_mailing',
            'begin' => '1554180370.4106'
        ]);
        
        // открываем файл
        $csv = fopen(SITE_ROOT .'/data/failedMailing.csv', 'ab');

        foreach($msg->http_response_body->items as $value) {
            $deliveryStatusCode = $value->{'delivery-status'}->code;
            $deliveryStatusMsg = $value->{'delivery-status'}->message;
            $reason = $value->reason;
            $email = $value->recipient;
            
            $message = [];
            $message[] = $email;
            $message[] = $reason;
            $message[] = $deliveryStatusCode;
            $message[] = $deliveryStatusMsg;

            // записываем строку в csv
            @$res = fputcsv($csv, $message, ';');            

            //echo "$email, $reason, $deliveryStatusCode, $deliveryStatusMsg <br>";
        }

        fclose($csv);

        $count = count($msg->http_response_body->items);
        $time = $msg->http_response_body->items[$count - 1]->timestamp;

    } catch (MailgunEngineException $e) {
        $msg = $e->getMessage();
    } catch (Exception $e) {
        $msg = $e->getMessage();
    } catch (ErrorException $e) {
        $msg = $e->getMessage();
    } finally {
        //echo '<pre>';
        echo "Timestamp: $time";
        //echo '</pre>';
    }
