<?php ## 

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    use engines\MailgunEngine;
    use exceptions\MailgunEngineException;
    use engines\DBConnection;
    use exceptions\DBException;

    
    try {
        $connect = DBConnection::create();
        $db = $connect->getConnection();
    } catch (DBException $e) {
        // если не подключилось - выбрасываем исключение
        echo 'db error';
        exit();
    }
    

    $csv_fin = fopen(SITE_ROOT . '/data/cryptocrm_new.csv', 'r');

    try {
        $query = "INSERT INTO temp_clients VALUES (null, null, 1, :name, :phone, :email, :city, :address, :specialization)";
        $stm = $db->prepare($query);

        $count = 0;
        while (($row = fgetcsv($csv_fin, 1000, ";")) !== false) {
            
           // print_r($row);

            $stm->bindValue(':name', $row[0], \PDO::PARAM_STR);
            $stm->bindValue(':phone', $row[1], \PDO::PARAM_STR);
            $stm->bindValue(':email', $row[2], \PDO::PARAM_STR);
            $stm->bindValue(':city', $row[3], \PDO::PARAM_STR);
            $stm->bindValue(':address', $row[4], \PDO::PARAM_STR);
            $stm->bindValue(':specialization', $row[5], \PDO::PARAM_STR);
            $stm->execute();

            $count++;
        }        

    } catch (Exception $e) {
        echo $e->getMessage();
        echo "<br>Row N: $count<br>";
    }

    fclose($csv_fin);

    echo "$count rows added";