<?php
// путь общих секций шаблонов
const COMMON_SECTIONS_PATH = TEMPLATES_PATH . '/common_sections';
// путь страниц
const TEMPLATES_PAGE_PATH = TEMPLATES_PATH . '/pages';

# ОБЩИЕ СЕКЦИИ
const COMMON_HEAD_S = COMMON_SECTIONS_PATH . '/tpl_head_section.phtml';
const SEARCH_ENGINE_STAT = COMMON_SECTIONS_PATH . '/searchEngineStat.html';
// секция основного меню
const NAV_SECTION = COMMON_SECTIONS_PATH . '/nav_section';
    // секция навигации с функцией логина и регистрации
    const TPL_NAV_SECTION = NAV_SECTION . '/tpl_nav_s.phtml';
        const TPL_NAV_S = NAV_SECTION . '/tpl_nav_section.html';
        const TPL_REG_S = NAV_SECTION . '/tpl_registration_section.html';
        const TPL_LOG_S = NAV_SECTION . '/tpl_login_section.html';
    // секция навигации аутентифицированного пользователя
    const TPL_NAV_A_SECTION = NAV_SECTION . '/tpl_nav_a_section.phtml';
    // секция навигации без функции логина и регистрации
    const TPL_NAV_SIMPLE_SECTION = NAV_SECTION . '/tpl_nav_s_section.html';
// секция footer
const TPL_FOOTER_SECTION = COMMON_SECTIONS_PATH . '/tpl_footer_section.html';

// секция редактирования пользовательских данных
const USER_SECTION = COMMON_SECTIONS_PATH . '/user_section';
    const TPL_USER_SECTION = USER_SECTION . '/tpl_user_edit_section.phtml';

# СТРАНИЦЫ (пути)
//const TEST_PAGE = TEMPLATES_PAGE_PATH . '/test_page';
const TEST_AUTH_PAGE = TEMPLATES_PAGE_PATH . '/test_auth_page';
const MAIN_PAGE = TEMPLATES_PAGE_PATH . '/main_page';
const SEARCH_PAGE = TEMPLATES_PAGE_PATH . '/search_page';
const CHOP_PAGE = TEMPLATES_PAGE_PATH . '/chop_page';
const CHOP_PA_PAGE = TEMPLATES_PAGE_PATH . '/chop_pa_page';
const EMAIL_C_PAGE = TEMPLATES_PAGE_PATH . '/confirm_email_page';
const RESET_PASSW_PAGE = TEMPLATES_PAGE_PATH . '/reset_password';
const ADMIN_PAGE = TEMPLATES_PAGE_PATH . '/administration';
const MODER_PAGE = TEMPLATES_PAGE_PATH . '/moderation';
const UNSUBSCRIBE_PAGE = TEMPLATES_PAGE_PATH . '/unsubscribe';
const CONDITIONS_PAGE = TEMPLATES_PAGE_PATH . '/conditions_page';
const ARTICLES_PAGE = TEMPLATES_PAGE_PATH . '/articles';
const SERVICES_PAGE = TEMPLATES_PAGE_PATH . '/service';
const CONTACT_PAGE = TEMPLATES_PAGE_PATH . '/contact_page';
const UNDER_MAINTENANCE_PAGE = TEMPLATES_PAGE_PATH . '/under_maintenance_page';

# ШАБЛОНЫ СТРАНИЦ
// страница подтверждения email
const TPL_EMAIL_CONFIRM_PAGE = EMAIL_C_PAGE . '/tpl_confirm_email.phtml';
    // секции CONFIRM_EMAIL
    const EMAIL_CONFIRM_SECTIONS = EMAIL_C_PAGE . '/sections';
        const TPL_EMAIL_CONFIRM_SECTION = EMAIL_CONFIRM_SECTIONS . '/tpl_email_c_section.phtml';

// страница сброса пароля
const TPL_PASSW_RESET_PAGE = RESET_PASSW_PAGE . '/tpl_reset_passw.phtml';
    // секции PASSW_RESET
    const PASSW_RESET_SECTIONS = RESET_PASSW_PAGE . '/sections';
        const TPL_PASSW_SECTION = PASSW_RESET_SECTIONS . '/tpl_passw_section.phtml';
    
// первая страница
const TPL_MAIN_PAGE = MAIN_PAGE . '/tpl_main.phtml';
    // секции MAIN_PAGE
    const MAIN_PAGE_SECTIONS = MAIN_PAGE . '/sections';
        const TPL_SEARCH_SECTION = MAIN_PAGE_SECTIONS . '/tpl_search_section.phtml';
        const TPL_HEADER_SECTION = MAIN_PAGE_SECTIONS . '/tpl_header_section.html';
        const TPL_USEFUL_TIPS = MAIN_PAGE_SECTIONS . '/tpl_useful_tips_section.html';
        const TPL_SEARCH_BY_CITY = MAIN_PAGE_SECTIONS . '/tpl_search_by_city_section.phtml';
    // JS
    const MAIN_PAGE_JS = MAIN_PAGE . '/js/main.js';

// страница поиска ЧОП
const TPL_SEARCH_PAGE = SEARCH_PAGE . '/tpl_search.phtml';
    // секции SEARCH_PAGE
    const SEARCH_PAGE_SECTIONS = SEARCH_PAGE . '/sections';
        const TPL_COMMON_NAV_SECTION = SEARCH_PAGE_SECTIONS . '/tpl_common_nav_section.phtml';
        const TPL_PARAMS_NAV_SECTION = SEARCH_PAGE_SECTIONS . '/tpl_params_nav_section.html';
        const TPL_RESULTS_SECTION = SEARCH_PAGE_SECTIONS . '/tpl_results_section.html';
        const TPL_PAGES_SECTION = SEARCH_PAGE_SECTIONS . '/tpl_pages_section.html';

// страница ЧОП
const TPL_CHOP_PAGE = CHOP_PAGE . '/tpl_chop.phtml';
    // секции CHOP_PAGE
    const CHOP_SECTIONS = CHOP_PAGE . '/sections';
        const TPL_CHOP_BL_NAV_S = CHOP_SECTIONS . '/tpl_chop_bl_nav_section.html';
        const TPL_CHOP_HEAD_S = CHOP_SECTIONS . '/tpl_chop_head_section.html';
        const TPL_CHOP_ABOUT_SERVICE_S = CHOP_SECTIONS . '/tpl_chop_about_service_section.html';
        const TPL_CHOP_PHOTO_KEY_FACTS_S = CHOP_SECTIONS . '/tpl_chop_photo_key_facts_section.html';
        const TPL_CHOP_FEEDBACK_CONTACTS_S = CHOP_SECTIONS . '/tpl_chop_feedback_contacts_section.html';

// страница ЛК ЧОП
const TPL_CHOP_PA_PAGE = CHOP_PA_PAGE . '/tpl_chop_pa.phtml';
    // секции CHOP_PA_PAGE
    const CHOP_PA_SECTIONS = CHOP_PA_PAGE . '/sections';
        // секция стандартное верхнее меню на черной полоске
        const TPL_CHOP_PA_BL_NAV_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_bl_nav_section.html';
        // страница закладок
        const TPL_CHOP_PA_TABS_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_tabs_section.html';
        // страницы закладки о компании
        const TPL_CHOP_PA_HEAD_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_head_section.html';
        const TPL_CHOP_PA_CONTACTS_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_contacts_section.html';
        const TPL_CHOP_PA_SERVICES_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_services_section.html';
        const TPL_CHOP_PA_ABOUT_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_about_section.html';
        const TPL_CHOP_PA_FOTO_LICENCE_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_foto_licence_section.html';
        const TPL_CHOP_PA_SUBMIT_END_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_submit_end_section.html';
        // страница Прейскурант
        const TPL_PRICE_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_price_section.html';
        // страница Поддержка
        const TPL_CHOP_PA_SUPPORT_S = CHOP_PA_SECTIONS . '/tpl_chop_pa_support_section.html';

// страница администратора
const TPL_ADMIN_PAGE = ADMIN_PAGE . '/tpl_admin.phtml';
    // секции ADMIN_PAGE
    const ADMIN_SECTIONS = ADMIN_PAGE . '/sections';
        const TPL_ADMIN_BL_NAV_S = ADMIN_SECTIONS . '/tpl_admin_bl_nav_section.html';
        const TPL_ADMIN_TABS_S = ADMIN_SECTIONS . '/tpl_admin_tabs_section.html';
        const TPL_USERS_ROLE_SECTION = ADMIN_SECTIONS . '/tpl_users_role_section.html';
        const TPL_MAILING_SECTION = ADMIN_SECTIONS . '/tpl_mailing_section.html';

// страница модерации
const TPL_MODER_PAGE = MODER_PAGE . '/tpl_moder.phtml';
    // секции MODER_PAGE
    const MODER_SECTIONS = MODER_PAGE . '/sections';
        const TPL_MODER_BL_NAV_S = MODER_SECTIONS . '/tpl_moder_bl_nav_section.html';
        const TPL_MODER_TABS_S = MODER_SECTIONS . '/tpl_moder_tabs_section.html';
        const TPL_MODER_SECTION = MODER_SECTIONS . '/tpl_moderation_section.html';
        const TPL_MODER_PAGES_S = MODER_SECTIONS . '/tpl_moder_pages_section.html';

// страница отписки от рассылки
const TPL_UNSUBSCRIBE_PAGE = UNSUBSCRIBE_PAGE . '/tpl_unsubscribe.phtml';
    // секции UNSUBSCRIBE_PAGE
    const UNSUBSCRIBE_SECTIONS = UNSUBSCRIBE_PAGE . '/sections';
        const TPL_UNSUBSCRIBE_S = UNSUBSCRIBE_SECTIONS . '/tpl_unsubscribe_section.phtml';

// страница Положений и условий сервиса
const TPL_CONDITIONS_PAGE = CONDITIONS_PAGE . '/tpl_conditions.phtml';
    // секции CONDITIONS_PAGE
    const CONDITIONS_PAGE_SECTIONS = CONDITIONS_PAGE . '/sections';
        const TPL_CONDITIONS_SECTION = CONDITIONS_PAGE_SECTIONS . '/tpl_conditions_section.html';

// страница статей и документации
const TPL_ARTICLES_PAGE = ARTICLES_PAGE . '/tpl_articles.phtml';
    // секции ARTICLES_PAGE
    const ARTICLES_PAGE_SECTIONS = ARTICLES_PAGE . '/sections';
        const GREY_BAR_ARTICLES_S = ARTICLES_PAGE_SECTIONS . '/tpl_grey_bar_section.html';
        const TPL_ARTICLES_SECTION = ARTICLES_PAGE_SECTIONS . '/tpl_articles_section.php';

// раздел сервисных вспомогательных страниц
const TPL_SERVICES_PAGE = SERVICES_PAGE . '/tpl_services.phtml';
    // секции SERVICES_PAGE
    const SERVICES_PAGE_SECTIONS = SERVICES_PAGE . '/sections';
        const GREY_BAR_SERVICES_S = SERVICES_PAGE_SECTIONS . '/tpl_grey_bar_section.html';
        const TPL_SERVICES_SECTION = SERVICES_PAGE_SECTIONS . '/tpl_services_section.php';
    const SERVICES_SECTIONS_PAGES = SERVICES_PAGE_SECTIONS . '/pages';

// раздел "Связь с нами"
const TPL_CONTACT_PAGE = CONTACT_PAGE . '/tpl_contact_page.phtml';
    // секции CONTACT_PAGE
    const CONTACT_PAGE_SECTIONS = CONTACT_PAGE . '/sections';
        const GREY_BAR_CONTACT_PAGE_S = CONTACT_PAGE_SECTIONS . '/tpl_grey_bar_section.html';
        const TPL_CONTACT_SECTION = CONTACT_PAGE_SECTIONS . '/tpl_contact_section.html';

// страница На сайте проводятся технические работы
const TPL_UNDER_MAINTENANCE_PAGE = UNDER_MAINTENANCE_PAGE . '/tpl_under_maintenance.phtml';
    // секции under maintenance
    const UNDER_MAINTENANCE_SECTIONS = UNDER_MAINTENANCE_PAGE . '/sections';
        const TPL_UNDER_MAINTENANCE_SECTION = UNDER_MAINTENANCE_SECTIONS . '/tpl_under_maintenance_section.html';

// тестовая страница
/*
const TPL_TEST_PAGE = TEST_PAGE . '/tpl_test_page.phtml';
    // секции TEST_PAGE
    const TEST_PAGE_SECTIONS = TEST_PAGE . '/sections';
    const TPL_TEST_PAGE_S = TEST_PAGE_SECTIONS . '/tpl_test_page_section.html';
*/

# МАРШРУТЫ
const ROUTS = [
    // главная страница
    '' => [
        'page' => TPL_MAIN_PAGE,
        //'caching' => [ 'max-age=28800', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => true
    ],
    // страница поиска
    'search' => [
        'page' => TPL_SEARCH_PAGE,
        //'caching' => [ 'max-age=28800', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => true
    ],
    // страница ЧОП
    'agency' => [
        'page' => TPL_CHOP_PAGE,
        //'caching' => [ 'max-age=86400', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => true
    ],
    // личный кабинет
    'personal_area' => [
        'page' => TPL_CHOP_PA_PAGE,
        'caching' => [ 'no-cache', 'no-store', 'must-revalidate' ],
        'role' => 'user admin moderator',
        'analytics' => true
    ],
    // страница подтверждения пароля
    'confirm_email' => [
        'page' => TPL_EMAIL_CONFIRM_PAGE,
        'caching' => [ 'private', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => true
    ],
    // страница сброса пароля
    'reset_password' => [
        'page' => TPL_PASSW_RESET_PAGE,
        'caching' => [ 'private', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => false
    ],
    // страница администрирования
    'administration' => [
        'page' => TPL_ADMIN_PAGE,
        'caching' => [ 'private', 'must-revalidate' ],
        'role' => 'admin',
        'analytics' => true
    ],
    // страница модерирования
    'moderation' => [
        'page' => TPL_MODER_PAGE,
        'caching' => [ 'private', 'must-revalidate' ],
        'role' => 'moderator admin',
        'analytics' => true
    ],
    // страница отписки от рассылки
    'unsubscribe' => [
        'page' => TPL_UNSUBSCRIBE_PAGE,
        'caching' => [ 'private', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => false
    ],
    // страница Условий сервиса
    'conditions' => [
        'page' => TPL_CONDITIONS_PAGE,
        //'caching' => [ 'max-age=86400', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => true
    ],
    // страница статей и документации
    'articles' => [
        'page' => TPL_ARTICLES_PAGE,
        //'caching' => [ 'max-age=86400', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => true
    ],
    // раздел сервисных страниц
    'service' => [
        'page' => TPL_SERVICES_PAGE,
        'caching' => [ 'no-cache', 'no-store', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => true
    ],
    // страница "Связь с нами"
    'contact_us' => [
        'page' => TPL_CONTACT_PAGE,
        'caching' => [ 'no-cache', 'no-store', 'must-revalidate' ],
        'role' => 'everybody',
        'analytics' => true
    ]
];

# ссылка в ЛК по ролям
const HOME_BY_ROLES = [
    'user' => 'personal_area',
    'moderator' => 'moderation',
    'admin' => 'administration'
];

# ШАБЛОНЫ ПОЧТОВЫХ СООБЩЕНИЙ
const TPL_TEST_MAIL = TEMPLATES_EMAIL_PATH . '/tpl_test_mail.html';
