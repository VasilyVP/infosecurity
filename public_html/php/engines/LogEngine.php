<?php ## Библиотека логгирования исключений и событий
    namespace engines;

    class LogEngine //implements LoggerInterface
    {
        private $logMessages = [];
        private $debugMessages = [];
        private $infoMessages = [];
        //private $startTime;
        //private $measuring;
        static private $object;
        
        ## создаем объект в единственном экземпляре
        private function __construct()
        {
            date_default_timezone_set("Europe/Moscow");

            // если мерим длительность, то учитываем ее
            //$this->measuring = $measuring;            
            //if ($measuring) $this->startTime = microtime(true);
        }

        ## конструктор объекта в единственном экземпляре
        static public function create($measuring = false)
        {
            if (is_null(self::$object)) self::$object = new self($measuring);
            
            return self::$object;
        }

        public function emergency($message, array $context = [])
        {
            if (count($context) > 0)
                $message = 'WHERE: {METHOD}. Message: ' . $message;

            $this->logMessages[] = $this->logMessage('EMERGENCY', $message, $context);
        }

        public function alert($message, array $context = [])
        {
            if (count($context) > 0)
                $message = 'WHERE: {METHOD}. Message: ' . $message;

            $this->logMessages[] = $this->logMessage('ALERT', $message, $context);
        }

        public function critical($message, array $context = [])
        {
            if (count($context) > 0)
                $message = 'WHERE: {METHOD}. Message: ' . $message;

            $this->logMessages[] = $this->logMessage('CRITICAL', $message, $context);
        }

        public function error($message, array $context = [])
        {
            if (count($context) > 0)
                $message = 'WHERE: {METHOD}. Message: ' . $message;

            $this->logMessages[] = $this->logMessage('ERROR', $message, $context);
        }

        public function warning($message, array $context = [])
        {
            if (count($context) > 0)
                $message = 'WHERE: {METHOD}. Message: ' . $message;

            $this->logMessages[] = $this->logMessage('WARNING', $message, $context);
        }

        public function notice($message, array $context = [])
        {
            if (count($context) > 0)
                $message = 'WHERE: {METHOD}. Message: ' . $message;

            $this->logMessages[] = $this->logMessage('Notice', $message, $context);
        }

        public function info($message, array $context = [])
        {
            if (count($context) > 0)
                $message = 'WHERE: {METHOD}. Message: ' . $message;

            $this->infoMessages[] = $this->logMessage('Info', $message, $context);
        }

        public function debug($message, array $context = [])
        {
            if (count($context) > 0)
                $message = 'WHERE: {METHOD}. Message: ' . $message;

            $this->debugMessages[] = $this->logMessage('DEBUGGING', $message, $context);
        }

        public function log($level = 'Info', $message, array $context = [])
        {
            $this->logMessages[] = $this->logMessage($level, $message, $context);
        }

        ## обработка плейсхолдеров в $message по контексту $context
        private function replacePlaceholders($message, array $context = [])
        {
            $replace = [];
            foreach ($context as $key => $value) {
                $replace['{' . $key . '}'] = $value;
            }
            return str_replace(array_keys($replace), array_values($replace), $message);
        }

        ## формирует сообщение в лог
        private function logMessage($type, $message, $context)
        {
            $localMessage = date("d.m.y - H:i:s") . " [$type]: ";
            $localMessage .= $this->replacePlaceholders($message, $context) . "\n";
            
            return $localMessage;
        }

        // записывает логи в файлы
        public function __destruct()
        {
            // если мерим длительность скрипта - считаем и пишем в основной лог
            if (SCRIPT_MEASURE) {
                $duration = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3);
                $scriptName = $_SERVER['PHP_SELF'];
                $this->debug("Script $scriptName duration: $duration sec");
            }

            if (count($this->logMessages) > 0)
                @file_put_contents(EXCEPTIONS_LOG, join('\n', $this->logMessages), FILE_APPEND | LOCK_EX);
            
            if (count($this->debugMessages) > 0)
                @file_put_contents(DEBUGGING_LOG, join('\n', $this->debugMessages), FILE_APPEND | LOCK_EX);

            if (count($this->infoMessages) > 0)
                @file_put_contents(INFORMATION_LOG, join('\n', $this->infoMessages), FILE_APPEND | LOCK_EX);
        }
    }
