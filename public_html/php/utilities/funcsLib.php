<?php ## Библиотека полезных функций

    namespace utilities;
    
    use exceptions\UtilsException;
    use engines\LogEngine;

    class funcsLib
    {
        /** Возвращает содержимое массива строк или массивов в байтах */
        public static function getStringsArrayMemSize($array)
        {
            // проверяем массив ли это
            if (!is_array($array)) return false;

            $size = 0;
            foreach($array as $value) {
                if (is_array($value)) $size += self::getStringsArrayMemSize($value);
                elseif (is_string($value)) $size += strlen($value);
                else throw new UtilsException("Contain's is not string or array value");
            }

            return $size;
        }

        /** Разбирает поле город (full) на Город, район и область и возвращает ассоциативный массив */
        public static function parseCitytoArr($cityInput)
        {
            if (!is_string($cityInput)) throw new UtilsException("Error from parseCityArr. Is't string on input value");

            $cityExp = explode(', ', $cityInput);

            $city['city'] = trim($cityExp[0], ',');

            switch (count($cityExp)) {
                case 1:
                    $city['area'] = '';
                    $city['region'] = '';
                    break;
                case 2:
                    $city['area'] = '';
                    $city['region'] = trim($cityExp[1], ',');
                    break;
                case 3:
                    $city['area'] = trim($cityExp[1], ',');
                    $city['region'] = trim($cityExp[2], ',');
                    break;
            }
            return $city;
        }

        /** Преобразует русский текст в английский транслит */
        public static function RuToEnTranslit($string)
        {
            $symRu = ['Я', 'я', 'Ю', 'ю', 'Ч', 'ч', 'Ш', 'ш', 'Щ', 'щ', 'Ж', 'ж', 'А', 'а', 'Б', 'б', 'В', 'в', 'Г', 'г', 'Д', 'д',
            'Е', 'е', 'Ё', 'ё', 'З', 'з', 'И', 'и', 'Й', 'й', 'К', 'к', 'Л', 'л', 'М', 'м', 'Н', 'н', 'О', 'о', 'П', 'п',
            'Р', 'р', 'С', 'с', 'Т', 'т', 'У', 'у', 'Ф', 'ф', 'Х', 'х', 'Ц', 'ц', 'Ы', 'ы', 'Ь', 'ь', 'Ъ', 'ъ', 'Э', 'э'];
            
            $symEn = ['Ya', 'ya', 'Yu', 'yu', 'Ch', 'ch', 'Sh', 'sh', 'Sh', 'sh', 'Zh', 'zh', 'A', 'a', 'B', 'b', 'V', 'v', 'G', 'g',
            'D', 'd', 'E', 'e', 'E', 'e', 'Z', 'z', 'I', 'i', 'J', 'j', 'K', 'k', 'L', 'l', 'M', 'm', 'N', 'n', 'O', 'o',
            'P', 'p', 'R', 'r', 'S', 's', 'T', 't', 'U', 'u', 'F', 'f', 'H', 'h', 'C', 'c', 'Y', 'y', '', '', '', '',
            'E', 'e'];

            $translited = '';

            for ($i=0, $c = mb_strlen($string); $i < $c; $i++ ) {
                $symb = mb_substr($string, $i, 1);
                $pos = array_search($symb, $symRu);
                if ($pos !== false) $translited .= $symEn[$pos];
                else $translited .= $symb;
            }

            return $translited;
        }

        /** производит обратное перекодирование html сущностей многоуровневого массива данных */
        public static function htmlEntityArrDecode($arr)
        {
            // если объект - преобразовываем в массив
            if (is_object($arr)) $arr = (array)$arr;

            $newArr = [];
            foreach($arr as $key => $val) {
                if (is_array($val)) $newArr[$key] = self::htmlEntityArrDecode($val);
                else $newArr[$key] = html_entity_decode($val);
            }
            return $newArr;
        }

        /** Рассчитывает количество результатов поиска на странице */
        public static function calcProvidersByPage($providersQuantity)
        {
            if ($providersQuantity <= 100) $providersByPage = 5;
            elseif ($providersQuantity <= 200) $providersByPage = 10;
            elseif ($providersQuantity <= 300) $providersByPage = 15;
            else $providersByPage = 20;

            return $providersByPage;
        }

        /** Возвращает сгенерированный пароль длинны length */
        public static function generatePassword($length = 10)
        {
            $symbols = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

            $length = min(mb_strlen($symbols), $length);

            $password = mb_substr(str_shuffle($symbols), 0, $length);

            return $password;
        }
        
    }
