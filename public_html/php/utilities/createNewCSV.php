<?php ## 

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    use engines\MailgunEngine;
    use exceptions\MailgunEngineException;
    use engines\DBConnection;
    use exceptions\DBException;

    /*
    try {
        $connect = DBConnection::create();
        $db = $connect->getConnection();
    } catch (DBException $e) {
        // если не подключилось - выбрасываем исключение
        echo 'db error';
        exit();
    }
    */

    // открываем файл
    $csv_old = fopen(SITE_ROOT . '/data/cryptocrm_new.csv', 'r');
    $csv_new = fopen(SITE_ROOT . '/data/cryptocrm_new_2.csv', 'r');

    $members = [];

    $rows_old = 0;
    $rows_new =0;
    $crm_old = [];
    $crm_new = [];

    // читаем записи из файла и добавляем поле name из БД
    while (($row = fgetcsv($csv_old, 1000, ";")) !== false) {
        
        if ($row[2] == 'email') continue;

        if ($row[2] != '') {
            $crm_old[] = $row;

            $emails_old[] = trim($row[2]);
        }

        $rows_old++;
    }

    fclose($csv_old);

    echo "Old: $rows_old<br>";

    while (($row = fgetcsv($csv_new, 1000, ";")) !== false) {

        if ($row[2] == 'email') continue;
        
        if ($row[2] != false && $row[3] != false && $row[2] != ' ' && $row[3] != ' ') {
            $crm_new[] = $row;

            $emails_new[] = trim($row[2]);
        }

        $rows_new++;
    }

    fclose($csv_new);

    echo "New: $rows_new<br>";

    /*
    $rows_fin = 0;
    $emails_fin = [];

    $csv_fin = fopen(SITE_ROOT . '/data/cryptocrm_new_2.csv', 'w');

    foreach($crm_new as $row) {
        if (in_array($row[2], $emails_old) == false) {
            
            if (in_array($row[2], $emails_fin)) continue;

            $row[2] = trim($row[2]);

            $crm_fin[] = $row;

            $emails_fin[] = $row[2];

            fputcsv($csv_fin, $row, ';');
         
            $rows_fin++;

            echo "$row[2]<br>";
        }
    }

    fclose($csv_fin);

    echo "Rows fin: $rows_fin";

    */

    echo '<pre>';
    print_r(array_diff($emails_old, $emails_new));
    echo '</pre>';  
    
