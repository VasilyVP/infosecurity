<?php ## Скрипт массовой загрузки пользователей из БД в список рассылки Mailgun

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    //use Mailgun\Mailgun;
    use engines\DBConnection;
    use engines\MailgunEngine;
    use exceptions\MailgunEngineException;
    use exceptions\DBException;

    try {
        $connect = DBConnection::create();
        $db = $connect->getConnection();
    } catch (DBException $e) {
        // если не подключилось - выбрасываем исключение
        echo 'db error';
        exit();
    }

    $mge = new MailgunEngine;

    $result = 'no';

    // читаем записи из таблицы
    try {
        $query = "SELECT name, email FROM temp_clients";
        $arr = $db->query($query)->fetchAll(PDO::FETCH_OBJ);
        $rowsCount = count($arr);
        echo "Count from fetchAll: $rowsCount<br>";
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    $i = 0;
    $c = 1;
    $members = [];
    foreach ($arr as $row) {
        $members[] = [
            'address' => $row->email,
            'name' => $row->name,
            'subscribed' => true,
            'vars' => [
                'unsubscribe_token' => $mge->getUnsubscribeToken($row->email)
            ]
        ];
        if ($i++ === 999) {            
            $allMembers[] = $members;            
            $members = [];
            $i = 0;
        }
    }
    $allMembers[] = $members;
    
    echo 'AllMembers: ' . count($allMembers) . '<br>';
    $sum = 0;

    foreach ($allMembers as $memb) {
        $sum += count($memb);

        try {
            $result = $mge->addManyUsersToMailingList(TEMPORARY_CLIENTS_MAILINGLIST, $memb, 'yes');            
        } catch (MailgunEngineException $e) {
            echo $e->getMessage();
        } catch (Exception $e) {
            echo $e->getMessage();
        } finally {
            if ($result == true) echo '==true<br>';
            elseif ($result == false) echo '==false<br>';
        }
    }
    echo "Sum = $sum";
