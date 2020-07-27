<?php # Скрипт запуска сервисов через WEB Server локально через CLI

    // !!! domain надо менять на домен сайта !!!
    $domain = 'https://scanox.pro';
    //$domain = 'https://development.scanox.pro';
    //$domain = 'http://infosecurity.info';

    ## Устанавливаем параметры запроса
    
    # Create Sitemap
    //$ php startSeoServices.php createSitemap cities|providers|queries
    if ($argv[1] == 'createSitemap') {
        //echo 'Sitemap creating for ' . $argv[2] . "\n";
        
        $query = $domain . '/php/services/createSitemap.php';

        $query .= '?token=405bd85d2652a56fab46c17de4e27a14';
        // параметр 2 - это тип сайтмэп: 'cities', 'providers', 'queries'
        $query .= '&mapName=' . $argv[2];
        
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $query,
            CURLOPT_RETURNTRANSFER => 1,
        //   CURLOPT_PROXYPORT => 3128,
            //CURLOPT_TIMEOUT => 2,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

    # SSR Cache pages
    //$ php startSeoServices.php cachePages siteMapFullFileName
    } elseif ($argv[1] == 'cachePages') {
        try {
            $logMsg = '';
            $file = $argv[2] ?? false;
            if (!($file && file_exists($file))) {
                throw new Exception('No sitemap file or invalid input parameter');
            }
        
            $start = microtime(true);

            $content = file_get_contents($file);
            // если заархивировано - разархивируем
            if (preg_match('/\.xml\.gz$/', $file)) {
                $content = gzdecode($content);
            }

            // преобразовываем xml строку в объект
            $i = 0;
            $siteMap = simplexml_load_string($content);

            foreach ($siteMap->url as $record) {
                // адрес запроса - адрес из sitemap
                $query = $record->loc;

                $chCache = curl_init();
                curl_setopt_array($chCache, [
                    CURLOPT_URL => $query,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_USERAGENT => 'Scanox cache initiator' // сделать проверку на этот UA в routing engine
                    //   CURLOPT_PROXYPORT => 3128,
                    //CURLOPT_TIMEOUT => 2,
                ]);

                $response = curl_exec($chCache);
                curl_close($chCache);

                $i++;
            }
            $end = microtime(true);
            $duration = $end - $start;

            // формируем сообщения для лога
            $logMsg = "$i pages cached from {$argv[2]}. Duration: $duration sec.";
        } catch (Exception $e) {
            $logMsg = $e->getMessage();
        } finally {
            // отправляем в лог
            $query = $domain . '/php/services/saveLogMessage.php';
            $query .= '?token=405bd85d2652a56fab46c17de4e27a14';
            $query .= '&message=' . urlencode($logMsg);
        
            $ch = curl_init();
        
            curl_setopt_array($ch, [
                CURLOPT_URL => $query,
                CURLOPT_RETURNTRANSFER => 1
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
        }
    } else {
        echo "Incorrect command\n";
        echo 'Use: $ php startService.php createSitemap|cachePages' . "\n";
    }
