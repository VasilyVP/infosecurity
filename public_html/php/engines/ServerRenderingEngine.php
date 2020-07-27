<?php /** Engine серверного рендеринга и кеширования страниц
        */
    namespace engines;

    use GuzzleHttp\Client;

    class ServerRenderingEngine
    {
        private $cacheFile = false;

        public function __construct()
        {
            $this->log = \engines\LogEngine::create();
        }

        /** Возвращает файл страницы из кэша или отрисовывает */
        public function getPage($reqURL)
        {
            // Формируем имя файла кэша
            $cacheFile = SSR_CACHE_DIR . '/' . MD5($reqURL) . '.html';
            // если файл в кэше - возвращаем его
            if (file_exists($cacheFile)) return $cacheFile;

            // если файла в кэше нет - формируем страницу по запросу
            $this->cacheFile = $cacheFile;

            return $this->renderAndCache($reqURL);
        }

        public function renderAndCache($reqURL)
        {
            // Формируем имя файла кэша
            $cacheFile = $this->cacheFile ? $this->cacheFile : SSR_CACHE_DIR . '/' . MD5($reqURL) . '.html';
            $this->cacheFile = false;

            try {
                $client = new Client([
                    'base_uri' => "http://localhost:3000/https://$reqURL",
                    'verify' => false
                ]);
                $response = $client->get('');
            } catch (Exception $e) {
                $this->log->error("SSR error, can't get page: " . $e->getMessage(), ['METHOD' => __METHOD__]);
            }            

            // пишем файл в кэш и возвращаем его для подключения в ответ
            $res = file_put_contents($cacheFile, $response->getBody());
            if ($res == false) $this->log->error("Can't write rendered page to the cache", ['METHOD' => __METHOD__]);
            return $cacheFile;
        }

    }
