// логика закладок
// добавляем свои модули
import OrganizationTab from './organization_tab.js';
import OrganizationTabImgsLoad from './organiz_tab_imgs_load.js';
import PriceTab from './price_tab.js';
import SupportTab from './support_tab.js';
import SuggestionGeo from './suggestionGeo.js';

// подключаем обработку Страниц
new OrganizationTab;
new OrganizationTabImgsLoad;
new PriceTab;
new SupportTab;
new SuggestionGeo;

// обрабатываем события перелистывания вкладок
// при открытии закладки Поддержка инициируем событие open для вкладки
/*
$('a[data-toggle="tab"').on('shown.bs.tab', function (event) {
    if (event.target.id == 'nav-support-tab') {
        $('#nav-support').trigger('open');  
    }
});
*/

// через 119 минут переходим на главную страницу чтобы не было действий после окончания авторизации
window.setTimeout(() => { window.location.href = window.location.origin; }, 7140000);