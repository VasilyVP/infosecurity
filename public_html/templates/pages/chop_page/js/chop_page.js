import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';
import Routing from '/js/modules/Routing.js';

// обработчик щелчка по изображению - увеличение
$('img.maximized').click(function (e) {
    document.getElementById('bigImage').src = e.target.src;
    $('#showPicture').modal({ backdrop: true });
});

// обработчик "Показать телефон"
$('a[name="show_phone"]').click(function (e) {
    e.preventDefault();
    $('p[name="phone"]').text($(this).data('phone'));
    $('a[name="show_phone"]').text('');
});

// формируем uid
const link = location.pathname.split('/')[2];

// загружаем данные страницы
$.getJSON(PATHS.getAgencyDataAPIurl, { link: link }, function (response) {
    // обработка ошибок
    if (response.code !== 1) {
        console.log(response);
        return;
    }
    const data = response.data;

    // секция about
    if (data.logo_flag == 'true') {
        $('#logo').prop('src', '/imgs/service_providers/' + response.folder + '/logo')
            .prop('alt', data.brand_name + ' logo')
            .attr('alt', data.brand_name + ' logo');
    } else {
        $('#logo').prop('src', '/imgs/icons/logo_chop.png');
    }

    $('#organization').text(data.organization);

    const primaryIndex = data.address_type.indexOf('primary');
    $('#city').text(data.city[primaryIndex]);
    $('a[name="show_phone"]').data('phone', data.main_phone);

    // если нет ни одного пункта из раздела - скрываем весь раздел
    if (!data.chop_about && !data.establishment_year && !data.clients_quantity && !data.employee_quantity && !data.gbr_quantity)
        $('#about_container').addClass('d-none');

    if (data.chop_about) {
        $('#about').html(data.chop_about.replace(/(\n)|(\r\n)|(\n\r)/g, '<br>'));
        $('#about_div').removeClass('d-none');
    }
    if (data.establishment_year) {
        $('#established_year').text(data.establishment_year);
        $('#established_div').addClass('d-flex').removeClass('d-none');
    }
    if (data.clients_quantity) {
        $('#clients_q').text(data.clients_quantity);
        $('#clients_q_div').addClass('d-flex').removeClass('d-none');
    }
    if (data.employee_quantity) {
        $('#employees').text(data.employee_quantity);
        $('#employees_div').addClass('d-flex').removeClass('d-none');
    }
    if (data.gbr_quantity) {
        $('#gbr_q').text(data.gbr_quantity);
        $('#gbr_q_div').addClass('d-flex').removeClass('d-none');
    }

    // секция Услуги
    if (data.service_about) {
        $('#service_about').html(data.service_about.replace(/(\n)|(\r\n)|(\n\r)/g, '<br>'));
    }
    if (data.pult_security_check) $('#pult_sec_serv').addClass('d-flex').removeClass('d-none');
    if (data.physic_security_check) $('#phys_sec_serv').addClass('d-flex').removeClass('d-none');
    if (data.collection_check) $('#collection_serv').addClass('d-flex').removeClass('d-none');
    if (data.cctv_check) $('#cctv_serv').addClass('d-flex').removeClass('d-none');
    if (data.design_installation_check) $('#design_serv').addClass('d-flex').removeClass('d-none');
    if (data.gps_check) $('#gps_serv').addClass('d-flex').removeClass('d-none');

    // выводим фотографии
    if (data.chop_photo_file_name.length !== 0) {
        const $photoSec = $('#photo_section');
        const $photoDiv = $('[name="photo_div"]', $photoSec);
        for (let i = 0, c = data.chop_photo_file_name.length; i < c; i++) {
            const photo = $photoDiv.clone(true).addClass('d-flex').removeClass('d-none').appendTo($photoSec).get(0);
            $('img', photo).prop('src', '/imgs/service_providers/' + response.folder + '/' + data.chop_photo_file_name[i])
                .prop('alt', data.chop_photo_name[i])
                .attr('alt', data.chop_photo_name[i]);
            $('[name="photo_title"]', photo).text(data.chop_photo_name[i]);
        }
        $('#photo_container').removeClass('d-none');
    }
    // выводим: Почему выбирают нас
    if (data.why_you_item.length !== 0) {
        const $whyWeDiv = $('#why_we_div');
        const $whyWeRow = $('[name="why_we_row"]', $whyWeDiv);
        for (let i = 0, c = data.why_you_item.length; i < c; i++) {
            const row = $whyWeRow.clone().appendTo($whyWeDiv).removeClass('d-none').get(0);
            $('img', row).prop('src', `/imgs/icons/Пункт-${i + 1}.png`);
            $('[name="why_title"]', row).text(data.why_you_item[i]);
        }
        $('#why_we_container').removeClass('d-none');
    }
    // выводим лицензии
    if (data.chop_licence_file_name.length !== 0) {
        const $licenceSec = $('#licence_section');
        const $licenceDiv = $('[name="licence_div"]', $licenceSec);
        for (let i = 0, c = data.chop_licence_file_name.length; i < c; i++) {
            const licence = $licenceDiv.clone(true).addClass('d-flex').removeClass('d-none').appendTo($licenceSec).get(0);
            $('img', licence).prop('src', '/imgs/service_providers/' + response.folder + '/' + data.chop_licence_file_name[i])
                .prop('alt', data.chop_licence_name[i])
                .attr('alt', data.chop_licence_name[i]);
            $('[name="licence_title"]', licence).text(data.chop_licence_name[i]);
        }
        $('#licence_container').removeClass('d-none');
    }
    
    // выводим отзывы
    /*
    if (data.feedback_file_name.length !== 0) {
        const $feedbackSec = $('#feedback_section');
        const $feedbackDiv = $('[name="feedback_div"]', $feedbackSec);
        for (let i = 0, c = data.feedback_file_name.length; i < c; i++) {
            const feedback = $feedbackDiv.clone(true).removeClass('d-none').appendTo($feedbackSec).get(0);
            $('a', feedback).prop('href', '/imgs/service_providers/' + response.folder + '/' + data.feedback_file_name[i]);
            $('[name="client_name"]', feedback).text(data.feedback_client_name[i]);
            $('[name="feedback_short"]', feedback).text(data.feedback_content[i]);
        }
        $('#feedback_container').removeClass('d-none');
    }
    */
   
    // выводим "С нами работают"
    if (data.client_item.length !== 0) {
        const $clientsSec = $('#clients_section');
        const $clientItem = $('[name="client_item"]', $clientsSec);
        for (let i = 0, c = data.client_item.length; i < c; i++) {
            $($clientItem).clone().removeClass('d-none').appendTo($clientsSec).text(data.client_item[i]);
        }
        $('#clients_container').removeClass('d-none');
    }
    // выводим Контакты
    $('#country').text(data.public_country + ',');
    const $addrDiv = $('#addresses_div');
    const $item = $('[name="address_item"]', $addrDiv);
    for (let i = 0, c = data.city.length; i < c; i++) {
        const item = $item.clone().removeClass('d-none').appendTo($addrDiv).get(0);

        let addrType = data.address_type[i] == 'primary' ? 'Основной: ' : 'Филиал: ';
        if (c === 1) addrType = '';

        $('[name="addr_type"]', item).text(addrType);
        $('[name="city"]', item).text(data.city[i]);
        
        let address = '';
        if (data.address[i]) address = ', ' + data.address[i];
        if (data.office[i]) address += ', ' + 'офис ' + data.office[i];
        $('[name="address"]', item).text(address);
    }
    // Email
    $('#email_t').text(data.chop_email);
    if (data.chop_email) $('#email_a').prop('href', 'mailto:' + data.chop_email);
    else $('#email_a').text('');
    // сайт
    $('#site_t').text(data.chop_site);
    if (data.chop_site) $('#site_a').prop('href', 'http://' + data.chop_site.replace(/^https?:\/\//, ''));
    else $('#site_a').text('');

    const coordsStr = `${data.longitude[primaryIndex]},${data.latitude[primaryIndex]}`;
    const mapAdr = `https://static-maps.yandex.ru/1.x/?ll=${coordsStr}&size=550,450&z=16&pt=${coordsStr},flag&l=map`;
    $('#map').prop('src', mapAdr);

    // если перешли извне - формируем и показываем ссылку "Перейти к поиску"
    if (!sessionStorage.location) {
        // иначе - переходим на страницу поиска этого города
        $('#go_to_search').text(data.city[primaryIndex]);
        
        const goToLoc = {
            country: data.public_country,
            full: data.city[primaryIndex]
        }

        const adr = new Routing().getSearchURL(goToLoc);

        // формируем ссылку на страницу поиска
        $('#go_back').prop('href', adr);
        $('#go_to_search_section').removeClass('d-none');
    }

    // формируем заголовок страницы, и description
    const servicesList = [];
    if (data.pult_security_check) servicesList.push('Пультовая охрана');
    if (data.physic_security_check) servicesList.push('Физическая охрана');
    if (data.cctv_check) servicesList.push('Видеонаблюдение');
    let services = servicesList.join('. ');
    document.title = data.organization + ' ' + data.city[primaryIndex] + '. ' + services + '. ';

    if (data.collection_check) servicesList.push('Инкассация');
    if (data.design_installation_check) servicesList.push('Проектирование и монтаж ОПС');
    if (data.gps_check) servicesList.push('GPS мониторинг автотранспорта');

    services = servicesList.join(', ');
    
    let trustUs = '';
    if (data.clients_quantity) trustUs = ` Нам доверяют ${data.clients_quantity} клиентов.`;
    
    let estYear = '';
    if (data.establishment_year) estYear = ` На рынке с ${data.establishment_year} года.`;
    
    const description = `${data.organization} ${data.city[primaryIndex]}. ${services}.${estYear}${trustUs}`;
    $('#description').prop('content', description);
});