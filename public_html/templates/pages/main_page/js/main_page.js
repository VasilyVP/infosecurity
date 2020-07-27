import { PATHS, KEYS, USE_SESSION_TOKEN } from '/js/config.js';
import DetermineLocation from '/js/modules/DetermineLocation.js';
import PlaceAutoCompliteG from '/js/modules/PlaceCompliteG.js';
import Routing from '/js/modules/Routing.js';
import Captcha from '/js/modules/reCAPTCHAhandler.js';

// активируем интерфейсы переклчателей
const $switch = $(".m_switch_check:checkbox");
$switch.mSwitch();

// визуализация переключения кнопок
$('button[data-toggle="tab"]').on('show.bs.tab', function (e) {
    $(e.target).add(e.relatedTarget).toggleClass('sx-btn-on').toggleClass('sx-btn-off');
});
// кнопка показать все для физиков
$('#show-phys-opt').click(function (e) {
    e.preventDefault();
    if ($(this).text() == 'Свернуть') $(this).text('Показать все');
    else $(this).text('Свернуть');
    $('#phys-options').toggleClass('d-none');
});
// кнопка показать все для юриков
$('#show-org-opt').click(function (e) {
    e.preventDefault();
    if ($(this).text() == 'Свернуть') $(this).text('Показать все');
    else $(this).text('Свернуть');
    $('#org-options').toggleClass('d-none');
});

// обрабатываем параметры сочетаемости опций
$switch.change(function () {
    // если выключается чекбокс - то ничего не делаем
    if (!this.checked) return;

    let $toUncheck;
    // если чекбокс для физиков
    if ($(this).parents('#nav-phys').length > 0) {
        $toUncheck = $switch.not(this);
    } else {
        // если для юриков
        const $signaling = $('#alarm_button, #sec_signaling, #fire_signaling');
        if ($signaling.is(this)) $toUncheck = $switch.not($signaling);
        else $toUncheck = $switch.not(this);
    }
    // выключаем и обнуляем переключатели
    $.mSwitch.turnOff($toUncheck);
    $toUncheck.prop('checked', false);
});

// получаем из полей data-country, city, area, region значение страны, города и т.д.
const $city = $('#city');
var locality = {};
locality.country = $('#country').data('country');
locality.full = $city.data('locality');
locality.city = $city.data('city');
locality.area = $city.data('area');
locality.region = $city.data('region');

// устанавливаем страну
if (locality.country) $('#country > option[value=' + locality.country + ']').prop('selected', true);
// устанавливаем город
//if (locality.full) $('#city').val(locality.full);
if (locality.city) $('#city').val(locality.city);

// если country и city не пустое, то определять местоположение не надо
let determinePlace = (locality.country && locality.city) ? false : true;

// инициируем геокодирование и сразу определяем местоположение (если надо)
const location = new DetermineLocation({
    coords: true,
    place: determinePlace,
    PATHS: PATHS,
    KEYS: KEYS
});
// инициируем подсказки
const suggestion = new PlaceAutoCompliteG({
    completionID: 'city',
    countryID: 'country',
    require: 'city',
    PATHS: PATHS,
    USE_SESSION_TOKEN: USE_SESSION_TOKEN
});

const determineLocality = function (localityDet) {
    if (determinePlace) {
        // устанавливаем страну
        $('#country > option[value=' + localityDet.country + ']').prop('selected', true);

        // устанавливаем город, если не федеральный центр, то Город и Область
        if (localityDet.city == localityDet.region || localityDet.city == localityDet.area) $city.val(localityDet.city);
        else $city.val(localityDet.city +
            //(localityDet.area ? `, ${localityDet.area}` : '') +
            (localityDet.region ? `, ${localityDet.region}` : ''));
        // формируем locality
        locality.country = localityDet.country;
        locality.city = localityDet.city;
        locality.full = $city.val();
    }
    // добавляем координаты
    locality.latitude = localityDet.latitude;
    locality.longitude = localityDet.longitude;
}

// устанавливаем значение поля населенного пункта если запущено определение местоположения
location.locationPr.then(determineLocality);

// обработчик нажатия "Определить" - определяем местоположение по геопозиции
$('#locate').click(e => {
    e.preventDefault();
    // отмечаем, что необходимо определить местоположение
    determinePlace = true;
    // инициируем объект определения местоположения
    const newLocation = new DetermineLocation({
        coords: true,
        place: determinePlace,
        PATHS: PATHS,
        KEYS: KEYS
    });
    newLocation.locationPr.then(determineLocality);
});

// добавляем признак ручного ввода
let handyInput = false;
$('#city').change(function () {
    handyInput = true;
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

// формируем все параметры, адресную строку и переходим по ней
$('#search_form').submit(function (e) {
    e.preventDefault();
    // удаляем captcha cookie
    document.cookie = 'captchaToken=;max-age=1;';

    // Формируем итоговое местоположение
    // если ввели вручную и не выбрали подсказку 
    if (handyInput && !suggestion.location.city) {
        locality = {};
    }
    locality.country = $('#country option:selected').text();
    locality.full = $city.val().trim();

    // параметры поиска
    // физики или юрики?
    const $who = $('div[data-function="params"].active');
    const who = $who.get(0).id;
    // собираем параметры
    const search = $(':input:checked', $who).serializeArray();

    // добавляем параметр, что с первой страницы (from = main)
    search.push({
        name: 'from',
        value: 'main'
    });

    // сохраняем локацию
    sessionStorage.location = JSON.stringify(locality);

    // переходим по новой ссылке
    window.location.href = new Routing().getSearchURL(locality, who, search);
});

// обработчик кнопки Присоединяйтесь
$('#to_register_btn').click(() => { $('#registration_link').trigger('click'); });

// обработчик клика по ссылке город: формируес куку с токеном капча и переходит по ссылке
async function setCaptchaCookie(e) {
    e.preventDefault();
    const el = this;
    // добавляем спинер
    el.innerHTML = el.innerText + 
        '<div class="spinner-border spinner-border-sm text-secondary ml-1"><span class="sr-only">Loading...</span></div>';

    // инициируем CAPTCHA
    const cap = new Captcha('main_page', KEYS);

    await cap.responsePromise().then(token => {
        cap.justSetCookie(token);
        // переходим по ссылке
        window.location.assign(el.href);
    });
}
    
// присваиваем обработчик нажатия ссылки Город
$('a[name="cityList"]').click(setCaptchaCookie);