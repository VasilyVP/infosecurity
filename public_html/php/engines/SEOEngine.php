<?php ## Механизм работы с задачами SEO
    namespace engines;

    class SEOEngine
    {
        // здесь формируем строку записей Sitemap
        private $siteMapRecords = '';

        # инициирует все входные переменные
        public function __construct()
        {
            // инициируем логи
            //$this->log = \engines\LogEngine::create();
        }

        /** Формирует запись Sitemap
         * $link - ссылка, $dateStr - строка с датой вида YYYY-MM-DD
        */
        public function addSitemapRecord($link, $dateStr, $changeFreq, $priority = 0.5)
        {
            $link = htmlspecialchars($link, ENT_QUOTES | ENT_XML1);

            $record = "<url>\n";
            $record .= "<loc>$link</loc>\n";
            $record .= "\t<lastmod>$dateStr</lastmod>\n";
            $record .= "\t<changefreq>$changeFreq</changefreq>\n";
            $record .= "\t<priority>$priority</priority>\n";
            $record .= "</url>\n";
            
            $this->siteMapRecords .= $record;
        }

        /** Формирует итоговую строку файла Sitemap */
        public function getSitemapStr()
        {
            $siteMap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $siteMap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            $siteMap .= $this->siteMapRecords;
            $siteMap .= '</urlset>';

            $this->siteMapRecords = '';
            
            return $siteMap;
        }


    }
