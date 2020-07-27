<?php
# ПУТИ
// путь корня сайта, защищенная область
define('SITE_ROOT', str_replace('/public_html', '', $_SERVER['DOCUMENT_ROOT']));
// путь стилей:
define('STYLES_PATH', $_SERVER['DOCUMENT_ROOT'] . '/styles');
// путь шаблонов:
define('TEMPLATES_PATH', $_SERVER['DOCUMENT_ROOT'] . '/templates');
// путь общих изображений
define('IMAGES_PATH', $_SERVER['DOCUMENT_ROOT'] . '/imgs');
// путь изображений ЧОП
const SERVICE_PROVIDERS_IMGS_PATH = IMAGES_PATH . '/service_providers';
// путь PHP скриптов
define('PHP_SCRIPTS_PATH', $_SERVER['DOCUMENT_ROOT'] . '/php/');
// путь логов
define('LOGS_PATH', SITE_ROOT . '/logs');
// путь сохранения сессий
define('SESSION_SAVE_PATH', SITE_ROOT . '/temp/php_sess');

// путь почтовых шаблонов
const TEMPLATES_EMAIL_PATH = TEMPLATES_PATH . '/emails';
// путь шаблонов почтовых рассылок
const TEMPLATES_MAILINGS_PATH = TEMPLATES_EMAIL_PATH . '/mailings/';

// путь изображений почтовых рассылок
const MAILINGS_IMGS_PATH = IMAGES_PATH . '/mailings/';

# Конфигурации страниц шаблонов
const TEMPLATES_CFG = PHP_SCRIPTS_PATH . 'templates_cfg.php';

# Конфигурация путей к файлам бандлов или точек сбора JS скриптов (для вариантов разработки и итоговой сборки)
const JS_ENTRY_PATHS_CFG = PHP_SCRIPTS_PATH . 'js_entry_paths_cfg.php';

# ЛОГИ
const EXCEPTIONS_LOG = LOGS_PATH . '/exceptions.log';
const DEBUGGING_LOG = LOGS_PATH . '/debugging.log';
const INFORMATION_LOG = LOGS_PATH . '/information.log';
const MEASUREMENT_LOG = LOGS_PATH . '/measurement.log';

# ДОМЕН
define('DOMAIN_NAME', $_SERVER['SERVER_NAME']);

# ГДЕ ЗАПУЩЕН СКРИПТ (prod, tes, dev, local)
if (DOMAIN_NAME == 'scanox.pro') define('EXECUTION_AT', 'production');
elseif (DOMAIN_NAME == 'development.scanox.pro') define('EXECUTION_AT', 'development');
elseif (DOMAIN_NAME == 'test.scanox.pro') define('EXECUTION_AT', 'test');
elseif (DOMAIN_NAME == 'infosecurity.info' || DOMAIN_NAME == 'localhost') define('EXECUTION_AT', 'localhost');

# ПОДКЛЮЧЕНИЯ К БД
//const DB_CONNECT = PHP_SCRIPTS_PATH . 'db_connect.php';
// разные параметры подключения в зависимости от места запуска
    // PRODUCTION
if (EXECUTION_AT == 'production') {
    define('HOSTING', true); // показывает, что хостинг
    define('HOST_NAME', 'localhost'); 
    define('DB_USER_NAME', 'scanox_db_user');
    define('DB_USER_PASSWORD', 'ukoLi6SKlb%y');
    define('DB_NAME', 'scanox_prod');
    // TEST
} elseif (EXECUTION_AT == 'test') {
    define('HOSTING', true); // показывает, что хостинг
    define('HOST_NAME', 'localhost'); 
    define('DB_USER_NAME', 'scanox_db_user');
    define('DB_USER_PASSWORD', 'ukoLi6SKlb%y');
    define('DB_NAME', 'scanox_test');
    // DEVELOPMENT
} elseif (EXECUTION_AT == 'development') {
    define('HOSTING', true); // показывает, что хостинг
    define('HOST_NAME', 'localhost'); 
    define('DB_USER_NAME', 'scanox_db_user');
    define('DB_USER_PASSWORD', 'ukoLi6SKlb%y');
    define('DB_NAME', 'scanox_dev');
    // LOCALHOST
} elseif (EXECUTION_AT == 'localhost') {
    define('HOSTING', false); // показывает, что не хостинг
    define('HOST_NAME', 'localhost');
    define('DB_USER_NAME', 'root');
    define('DB_USER_PASSWORD', '');
    define('DB_NAME', 'scanox');    
}

# ПАРАМЕТРЫ СКРИПТОВ
// подключать модуль логирования
//const LOGGING = true;
// мерить производиетльность скрипта
const SCRIPT_MEASURE = false;

# Использовать только HTTPS для кук сессии
if (EXECUTION_AT == 'production') define('COOKIE_SECURE', true);
else define('COOKIE_SECURE', false);
//const COOKIE_SECURE = false;

# mail code секретный ключ
const SECRET_MAIL_KEY = XXX;

# JWT секретный ключ
//const SECRET_KEY_JWT = XXX;

# reCAPCHA секретный ключ
const CAPCHA_SECRET_KEY = XXX;

# Mailgun
// Mailgun sandbox
//const MAILGUN_SB_DOMAIN = ;
//const MAILGUN_SB_API_KEY = ;
//const MAILGUN_
// scanox.pro domain
const MAILGUN_PRIVATE_API_KEY = ;
const MAILGUN_DOMAIN = ;
const MAILGUN_MAILING_DOMAIN = ;
const MAILGUN_FROM_DOMAIN = 'scanox.pro';
const MAILGUN_ENCRYPTION_KEY = ;

# Domain for sending mails from server through third smtp (NOT MAILGUN)
const DOMAIN_MAIL_NAME = 'scanox.pro';

#Mails name
const EMAIL_SENDER_NAME = 'Scanox';

# Mailing lists
if (EXECUTION_AT == 'production') {
    //define('TEMPORARY_CLIENTS_MAILINGLIST', XXX);
    define('USERS_MAILINGLIST', XXX);
    //define('TEST_MAILINGLIST', XXX);
} elseif (EXECUTION_AT == 'development') {
    //define('TEMPORARY_CLIENTS_MAILINGLIST', XXX);
    define('USERS_MAILINGLIST', XXX);
    //define('TEST_MAILINGLIST', XXX);
} elseif (EXECUTION_AT == 'test') {
    //define('TEMPORARY_CLIENTS_MAILINGLIST', XXX);
    define('USERS_MAILINGLIST', XXX);
    //define('TEST_MAILINGLIST', XXX);
} elseif (EXECUTION_AT == 'localhost') {
    //define('TEMPORARY_CLIENTS_MAILINGLIST', XXX);
    define('USERS_MAILINGLIST', XXX);
    //define('TEST_MAILINGLIST', XXX);
}
define('TEMPORARY_CLIENTS_MAILINGLIST', XXX);
define('TEST_MAILINGLIST', XXX);

# Support e-mail:
const SUPPORT_EMAIL = 'support@scanox.pro';
const SUPPORT_NAME = 'Scanox support';
# Sales e-mail:
const SALES_EMAIL = 'sales@scanox.pro';
const SALES_NAME = 'Scanox sales';

# WebHooks data
const FAILED_EMAILS = SITE_ROOT . '/data/failedEmails.csv';
const CLICKED_EMAILS = SITE_ROOT . '/data/clickedEmails.csv';
const OPENED_EMAILS = SITE_ROOT . '/data/openedEmails.csv';

# Параметры сессии
const SESSION_NAME = 'SCANOXSESS';
const SESSION_LIFE = 7200; // 2 часа

# ПУТИ СЕРВИСОВ API
const PATHS = [
    'reCAPCHAsiteVerify' => 'https://www.google.com/recaptcha/api/siteverify'
];

# ПАРАМЕТРЫ ЛК КЛИЕНТОВ
const IMAGE_MAX_SIZE = 500000;
// максимальный объем строковых данных страницы Организация ЧОП
const MAX_MEM_CLIENT_STRING_DATA = 100000;
// сколько файлов у ЧОПа
const MAX_FILES_QUANTITY = 10;
const MODERATION_EVERY_CHANGE = false;

// показывать временно загруженные ЧОПы
const USE_TEMP_CLIENTS = true;

# БЛОК SEO
// Количество в ТОПе городов для построения сайтмэпа по запросам
const SEO_TOP_CITIES = 100;
// Города для добавления к ТОП
const SEO_ADD_CITIES = [
    'nizhnij_novgorod' => 'rossiya',
    'chelyabinsk' => 'rossiya',
    'samara' => 'rossiya',
    'rostov-na-donu' => 'rossiya',
    'ufa' => 'rossiya',
    'krasnodar' => 'rossiya'
];
// Файл описания запросов queriesSitemap.csv
define('QUERIES_SITEMAP_SOURCE', SITE_ROOT . '/data/queriesSitemap.csv');

// Robots info directory
define('ROBOTS_INFO', $_SERVER['DOCUMENT_ROOT'] . '/robots_info');
// Sitemap files
define('SITEMAP_FILES', [
    'cities' => ROBOTS_INFO . '/sitemapcities.xml',
    'providers' => ROBOTS_INFO . '/sitemapproviders.xml.gz',
    'queries' => ROBOTS_INFO . '/sitemapqueries.xml.gz'
]);

# Блок Server Side Rendering
const SSR_CACHE_DIR = SITE_ROOT . '/temp/ssr_cache';

# БЛОК SERVICE PAGES and NOTIFICATIONS
const SITE_UNDER_MAINTENANCE = false;
# end of config files

# замена type="module" в тэге <script>
if (EXECUTION_AT == 'production' || EXECUTION_AT == 'test') define('TYPE_MODULE', '');
else define('TYPE_MODULE', 'type="module"');
