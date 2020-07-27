<?php ## Скрипт массового update/загрузки пользователей в списке рассылки Mailgun до 1000 за раз

    use engines\MailgunEngine;
    use exceptions\MailgunEngineException;
    use engines\DBConnection;
    use exceptions\DBException;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    try {
        $connect = DBConnection::create();
        $db = $connect->getConnection();
    } catch (DBException $e) {
        // если не подключилось - выбрасываем исключение
        echo 'db error';
        exit();
    }

    $mge = new MailgunEngine;

    // открываем файл
    $csv = fopen(SITE_ROOT . '/data/temp_clients.csv', 'r');

    $members = [];
    $i = 0;
    $n = 1;

    // читаем записи из файла и добавляем поле name из БД
    try {
        //$query = "SELECT name, surname FROM users WHERE email = :email";
        //$stm = $db->prepare($query);
        
        //PDO::FETCH_OBJ

        while (($row = fgetcsv($csv, 1000, ",")) !== false) {
            
            if ($row[2] != 'True') continue;

            if ($row[3] == '') continue;

            //$stm->bindValue(':email', $email[0], \PDO::PARAM_STR);
            //$stm->execute();
            //$name = $stm->fetchColumn();
            //$user = $stm->fetchObject();

            // формируем name и  email
            $name = $row[1];
            $email = $row[3];
            // формируем массив записей
            $members[] = [
                    'address' => $email,
                    'subscribed' => true,
                    'name' => $name,
                    'vars' => [
                        'unsubscribe_token' => $mge->getUnsubscribeToken($email)
                    ]
                ];

            //echo "$name $email<br>";
                
            // группируем и записываем по спискам рассылки
            if ($i == 190 || $i == 600 || $i == 1400 || $i == 2399 || $i == 2740) { //$i == 190 || $i == 600 || $i == 1400 || 
                // добавляем записи в список рассылки
                try {
                    $result = $mge->addManyUsersToMailingList("temp_clients_$n@mg.scanox.pro", $members, 'yes');
                    // $result = $mge->updateUserAtMailingList('temp_clients@mg.scanox.pro', $email, ['subscribed' => false]);
                } catch (MailgunEngineException $e) {
                    echo $e->getMessage() . '<br>';
                } catch (Exception $e) {
                    echo $e->getMessage() . '<br>';
                } finally {
                    
                    if ($result == true) {
                        echo "Updated $n<br>";
                    } elseif ($result == false) {
                        echo 'Error<br>';
                    }
    
                }
                   
                echo "I: $i<br>";
                echo "N: $n<br>";

                $members = [];

                $n++;
            }

            $i++;
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

   // $n++;
    echo "Last email: $email<br>";
   // echo "I: $i<br>";
   // echo "N: $n<br>";
    /*
    try {
        $result = $mge->addManyUsersToMailingList("temp_clients@mg.scanox.pro", $members, 'yes');
    } catch (MailgunEngineException $e) {
        echo $e->getMessage();
    } catch (Exception $e) {
        echo $e->getMessage();
    } finally {
        if ($result == true) {
            echo "Updated $n<br>";
        } elseif ($result == false) {
            echo 'Error<br>';
        }
    }
    */

    fclose($csv);
    
    /*
    echo '<pre>';
   // print_r($members);
    echo '</pre><br>';

    echo "Count: $i";
    */
