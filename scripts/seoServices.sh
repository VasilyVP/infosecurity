#!/bin/bash

# creating sitemaps
#php /var/www/html/scanox.pro/public_html/php/utilities/startSeoServices.php createSitemap cities
php /var/www/html/scanox.pro/public_html/php/utilities/startSeoServices.php createSitemap providers
php /var/www/html/scanox.pro/public_html/php/utilities/startSeoServices.php createSitemap queries

# clearing cache folder
rm -f /var/www/html/scanox.pro/temp/ssr_cache/*

# creating SSR cache
#php /var/www/html/scanox.pro/public_html/php/utilities/startSeoServices.php cachePages /var/www/html/scanox.pro/public_html/robots_info/sitemapcities.xml
php /var/www/html/scanox.pro/public_html/php/utilities/startSeoServices.php cachePages /var/www/html/scanox.pro/public_html/robots_info/sitemapproviders.xml.gz
php /var/www/html/scanox.pro/public_html/php/utilities/startSeoServices.php cachePages /var/www/html/scanox.pro/public_html/robots_info/sitemapqueries.xml.gz