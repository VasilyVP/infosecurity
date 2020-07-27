import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';
import DetermineLocation from '/js/modules/DetermineLocation.js';
import PlaceAutoCompliteG from '/js/modules/PlaceCompliteG.js';
import Rendering from './renderingSearch.js';
import Titles from '/js/modules/Titles.js';

// инициируем подсказки
const suggestion = new PlaceAutoCompliteG({
    completionID: 'city',
    countryID: 'country',
    require: 'city',
    PATHS: PATHS,
    USE_SESSION_TOKEN: USE_SESSION_TOKEN
});

// инициируем геокодирование
const location = new DetermineLocation({
    coords: false,
    place: false,
    PATHS: PATHS,
    KEYS: KEYS
});

// забираем locality из хранилища, если есть
let locality = {}
if (sessionStorage.location || false) locality = JSON.parse(sessionStorage.location);

// подключаем модуль оттображения
const render = new Rendering();

// обработка переключения и визуализация кнопок физики/юрики и актуализации select с видом сервиса
$('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
    // очищаем результаты поиска
    render.clearResults();
    // оформление переключателей
    $(e.target).add(e.relatedTarget).children().toggleClass('sx-color');
    // запускаем событие обновление вида сервиса или физики/юрики
    $('select[data-parameter="service-type"]').trigger('change'); //, $($(e.target).attr('href'))
});

const determineLocality = function (localityDet) {
    // if (determinePlace) {
    // устанавливаем страну
    $('#country > option[value=' + localityDet.country + ']').prop('selected', true);

    const $city = $('#city');
    // устанавливаем город, если не федеральный центр, то Город и Область
    if (localityDet.city == localityDet.region || localityDet.city == localityDet.area) $city.val(localityDet.city);
    else $city.val(localityDet.city +
        //(localityDet.area ? `, ${localityDet.area}` : '') +
        (localityDet.region ? `, ${localityDet.region}` : ''));
    // формируем locality
    locality.country = localityDet.country;
    locality.city = localityDet.city;
    locality.full = $city.val();
    //  }
    // добавляем координаты
    locality.latitude = localityDet.latitude;
    locality.longitude = localityDet.longitude;
}

// обработчик нажатия "Определить" - определяем местоположение по геопозиции
$('#locate').click(e => {
    e.preventDefault();
    // отмечаем, что необходимо определить местоположение
    const determinePlace = true;
    // инициируем объект определения местоположения
    const newLocation = new DetermineLocation({
        coords: true,
        place: determinePlace,
        PATHS: PATHS,
        KEYS: KEYS
    });
    newLocation.locationPr.then(determineLocality);
});

// заполняем страну и адрес
render.setFullAddrCountry();

// управляем появлением параметров при изменении типа сервиса (сигнализация, видео, GPS...)
const $serviceType = $('select[data-parameter="service-type"]');
$serviceType.change(render.orgPhysParamsShow);

let changed = false;
// после загрузки
$(function () {
    // отображаем все параметры поиска
    render.setSearchParameters();
    // инициируем проверку типа сервиса
    $serviceType.trigger('change');

    // инициируем вывод результатов поиска при загрузке
    // или для Юрлиц инициируем вывод после отрисовки интерфейса
    if (render.routing.who == 'nav-org') {
        $('#toggle-org').one('shown.bs.tab', function (e) {
            $('#showOffersOrg').trigger('click');
        });
        // для вкладки "Физическим лицам"
    } else $('#showOffersPhys').trigger('click');


    // добавляем признак изменения параметров поиска (если перешли с главной по городу и изменили переключатель)
    $(':input').change(() => {
        // формируем признак, что меняли параметры
        changed = true;
        // смтраницу в routing меняем на 1ю, чтобы не показывал при поиске по параметрам
        render.routing.page = 1;
    });
});

// управление появлением спецификации
$('a[name="show-spec"]').click(function (e) {
    e.preventDefault();
    $(this).parentsUntil('div[name="container"]').children('div[name="spec"]').toggleClass('d-none');
    if ($(this).text() == 'Свернуть') $(this).text('Показать состав услуги');
    else $(this).text('Свернуть');
});

// обработчик события выбора подсказки
// дополняем suggestion координатами
$('.typeahead').on('suggestion', (e, localitySugg) => {
    // осуществляем геокодирование места по G place_id
    location.getCoordinatesByPlaceIdPr(localitySugg.place_id).then(coords => {
        suggestion.location.latitude = coords.lat;
        suggestion.location.longitude = coords.lng;

        //временно запоминаю country, чтобы не потерять        
        const country = locality.country;
        locality = suggestion.location;
        locality.country = country;
    });
});

// добавляем признак ручного ввода
let handyInput = false;
$('#city').change(function () {
    handyInput = true;
});

/**  Обрабатываем кнопку "Показать предложения" */
$('#showOffersOrg, #showOffersPhys').click(function (e, page = false, quantity = false) {
    const thisObj = this;
    // заголовок
    const $caption = $('#caption');
    const $description = $('#description');

    // очищаем предыдущие результаты
    render.clearResults();

    // формируем параметры поиска
    let search = [];
    let who = false;

    // если город не указан - переходим на главную страницу
    if (render.routing.locality.city == '') window.location = '/';

    // если не переход по Городу и не меняли параметры поиска
    if (!render.routing.search.includes('service-type=all') || changed) {
        // формируем контекст физики/юрики
        const $context = $(this).parents('div[role="tabpanel"]');

        // параметры search
        search = $(':input:visible', $context).serializeArray();

        // если сигнализация и не выставлено параметров выставляем Охранную сигнализацию (по умолчанию)
        // если есть хоть один параметр кроме Пожарки, то тоже выставляем Охранную сигнализацию (в дополнение)
        const values = search.map(el => el.value);
        if (values.includes('signaling')) {
            // на закладке физики
            if ($('#nav-phys').hasClass('active')) {
                const names = search.map(el => el.name);
                // задаем параметры, которые требуют обязательного включенного параметра "Охранная сигнализация"(sec_signaling)
                const secSignalingRequireOpt = ['alarm_button', 'water_leak', 'gas_leak', 'glass_break'];
                // проверяем есть ли установленные параметры в массиве параметров Охранной сигнализации
                const requireSec = names.some((el, i) => { return secSignalingRequireOpt.includes(el); });

                if (!values.includes('on') || requireSec) {
                    const $check = $('input[name="sec_signaling"]:visible');
                    $check.prop('checked', true);
                    $.mSwitch.turnOn($check);
                    // параметры search
                    search = $(':input:visible', $context).serializeArray();
                }
            // на закладке юрики
            } else if ($('#nav-org').hasClass('active')) {
                if (!values.includes('on')) {
                    const $check = $('input[name="sec_signaling"]:visible');
                    $check.prop('checked', true);
                    $.mSwitch.turnOn($check);
                    // параметры search
                    search = $(':input:visible', $context).serializeArray();
                }
            }
        }
        // who - кто ищет юрики/физики
        who = $context.get(0).id;

        // если переход по Городу, то ищем все
    } else {
        search.push({ name: 'service-type', value: 'all' });
        // обнуляем местоположения, чтобы не попадали в поиск старые запросы
        sessionStorage.removeItem(location);
        locality = {};
    }

    // актуализируем локацию
    // если ввели вручную и не выбрали подсказку 
    if (handyInput && !suggestion.location.city) {
        locality = {};
    }
    locality.country = $('#country option:selected').text();
    locality.full = $('#city').val().trim();

    // если город пустой - заполняем из хранилища
    if (locality.full == '') {
        locality.full = sessionStorage.location || false ? JSON.parse(sessionStorage.location).full : '';
        $('#city').val(locality.full);
    }

    // получаем текущую страницу запроса: если извне пришли, то из URL
    if (!page) page = render.routing.page;

    // если в URL нет, то пришли с main_page страницы
    if (!page) page = 1;
    const currentPage = page;

    // параметры запроса
    const params = {
        locality: locality,
        search: search,
        page: currentPage,
        providersQuantity: quantity,
        who: who || false
    }

    // формируем caption, title и description
    const titles = new Titles();
    const title = titles.getCaption({ locality: locality, search: search });
    $caption.text(title.caption);
    document.title = title.caption + ' Все предложения на Scanox.pro';
    $description.prop('content', title.description);

    // запрос на новые результаты
    $.post(PATHS.getProvidersOffersAPIurl, params, function (response) {
        // обработка ошибок
        if (response.code !== 1) {
            console.log(response);
            return;
        }

        // проверяем на наличие результатов
        if (response.data.quantity == 0) {
            $('#find_nothing').removeClass('d-none');
            $caption.text('');
            return;
        }

        // выводим страничную навигацию, если страниц больше 1
        const quantity = response.data.quantity;
        const providersByPage = response.data.providersByPage;
        const pages = Math.ceil(quantity / providersByPage);

        // если страница не существует
        if (currentPage > pages) document.location.assign('/');

        // запрет индексирования и переходов для страниц больше 1ой
        const $robotsMeta = $('#robots_meta');
        if (currentPage > 1) $robotsMeta.attr('name', 'robots').attr('content', 'none');
        else $robotsMeta.removeAttr('content');
    
        // показываем карточки ЧОП
        render.showSearchResults(response.data.providers, title.cardCaption, search);

        // выводим страничную навигацию
        render.showPagesNav(pages, quantity, currentPage, thisObj);

    }, 'json'); //, 'json'

    // получаем новый URL
    const URL = render.routing.getSearchURL(locality, who, search, currentPage);

    // записываем состояние и обновляем URL в адресе
    history.pushState(locality, 'search page', URL);

    // записываем в sessionStorage locality
    sessionStorage.location = JSON.stringify(locality);
});

// обрабатываем кнопку "Показать телефон"
$('button[name="show_phone"]', '#search-results').click(function () {
    const $this = $(this);
    const phone = $this.data('phone') || 'Не указан';
    $this.addClass('font-weight-bold').addClass('text-dark').html(`<a href="tel:${phone}">${phone}</a>`);
});