<?php ## механизм обработки шаблона
    namespace engines;

    class TemplateProcessing 
    {
        //static private $object;
        private $values = array();
        private $page;
        private $log;
        
        /*
        private function __construct()
        {
            $this->log = \engines\LogEngine::create();
        }
        */
        
        /*
        static public function create()
        {
            if (is_null(self::$object)) self::$object = new self();
            
            return self::$object;
        }
        */

        public function loadPage($page)
        {
            $this->page = $page;
        }
        
        // SET one value function
        public function setValue($key, $value)
        {
            $key = '{' . $key . '}';
            $this->values[$key] = $value;
        }
    
        // SET any values function
        public function setValues(array $values)
        {
            foreach ($values as $key => $value) {
                $key = '{' . $key . '}';
                $this->values[$key] = $value;
            }
        }        
    
        // PARSING template function
        public function parseTemplate($page)
        {
            $this->page = str_replace(array_keys($this->values), array_values($this->values), $page);
        }

        // returns prepared page
        public function getPage()
        {
            return $this->page;
        }

    }
