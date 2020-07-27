import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';
import DetermineLocation from '/js/modules/DetermineLocation.js';
import Interface from '/js/modules/InterfaceLib.js';
import PhoneMask from '/js/modules/PhoneMaskByCountry.js';

export default class OrganizationTab {
    constructor() {
        const location = new DetermineLocation({
            coords: false,
            //place: false,
            PATHS: PATHS,
            KEYS: KEYS
        });

        // запоминаем форму
        const $orgForm = $('#chop_form');

        // подключаем маску ввода на телефон и ее обработчик
        const phoneMask = new PhoneMask('#main_phone');

        const $phoneCode = $('#phone_code');
        $phoneCode.change(function (e) {
            const phoneCode = $('option:selected', this).val().match(/\+\d+/)[0];
            // применяем маску
            phoneMask.applyMask(phoneCode);
        });
        $phoneCode.trigger('change');

        // если выбрана Пультовая сигнализация - показываем чекбоксы Проводная/Беспроводная и Опции
        const $pultSecCheck = $('#pult_security_check');
        $pultSecCheck.change(function (e) { //, priceCheck
            if (this.checked) {
                // показываем Виды сигнализации и Опции
                $('#signaling_type_s, #signaling_options_s').removeClass('d-none');

                // значения по умолчанию
                //if (!priceCheck) {
                    $('#wired_check').get(0).checked = true;
                    $('#wireless_check').get(0).checked = true;
                    // инициируем событие на проверку совместного отключения wired + wireless
                    $('#wired_check, #wireless_check').trigger('change');
                //}
            } else {
                // скрываем опции и тип сигнализации
                $('#signaling_type_s, #signaling_options_s').addClass('d-none');
                // обнуляем опции
                $('#wired_check').get(0).checked = false;
                $('#wireless_check').get(0).checked = false;
                $('#gas_check').get(0).checked = false;
                $('#water_check').get(0).checked = false;
            }
        });
        // показываем нужные чекбоксы по сигнализации по загрузке
        $pultSecCheck.on('onLoad', function () {
            if (this.checked)
                // показываем Виды сигнализации и Опции
                $('#signaling_type_s, #signaling_options_s').removeClass('d-none');
        });

        // если Проводная и беспроводна выключены, то и Пультовая выключается
        $('#wired_check, #wireless_check').change(function () {
            if (!$('#wired_check').get(0).checked && !$('#wireless_check').get(0).checked) {
                $pultSecCheck.get(0).checked = false;
                $pultSecCheck.trigger('change');
            }
        });

        // добавляем пункт в раздел Почему Вы
        $('#add_why_you').click(function (e) {
            const $whyList = $('#why_list');
            // проверяем кол-во записей
            const count = $whyList.data('count');
            if (count == 5) return;

            const $whyYouInput = $('#why_you_input');
            const whyYouInputVal = $whyYouInput.val().trim();
            // проверяем на пустые значения
            if (whyYouInputVal == '') return;

            // клонируем секцию и вставляем в нее текст
            $('li[name="why-you-element"]:first').clone(true).appendTo($whyList).removeClass('d-none');

            $('input[name="why_you_item[]"]:last').val(whyYouInputVal);
            $whyYouInput.val('');

            // добавляем счетчик
            $whyList.data('count', count + 1);
        });
        // удаляем пункт в разделе Почему Вы и уменьшаем счетчик
        $('button[name="why-you-delete"]').click(function (e) {
            const $whyList = $('#why_list');
            $(this).parents('li[name="why-you-element"]').remove();
            $whyList.data('count', $whyList.data('count') - 1);
        });

        // добавляем пункт в раздел Наши клиенты
        $('#add_client_item').click(function (e) {
            const $clientsCollection = $('#chop_clients_collection');
            // проверяем кол-во записей
            const count = $clientsCollection.data('count');
            if (count == 20) return;

            const $clientTextInput = $('#client_text_input');
            const clientTextInputVal = $clientTextInput.val().trim();

            // проверяем на пустые записи
            if (clientTextInputVal == '') return;

            // клонируем секцию и вставляем в нее текст
            $('div[name="chop_client_element"]:first').clone(true).appendTo($clientsCollection).removeClass('d-none');
            
            $('input[name="client_item[]"]:last').val(clientTextInputVal);
            $clientTextInput.val('');
            // добавляем счетчик
            $clientsCollection.data('count', count + 1);
        });
        // удаляем пункт в разделе Наши клиенты
        $('button[name="client-item-delete"]').click(function (e) {
            const $clientsCollection = $('#chop_clients_collection');
            $(this).parents('div[name="chop_client_element"]').remove();
            $clientsCollection.data('count', $clientsCollection.data('count') - 1);
        });

        // добавление строки адреса для ЧОП
        function addAddress() {
            return new Promise((resolve, reject) => {
                // проверяем уже введенное количество пунктов
                if (citiesQuantity == 3) return;

                // копируем поля ввода в поля хранения
                let city = $('#city').val().trim();
                city = city.replace(/^[ ]?г\.?(ород)?[ ]/i, '');
                
                const address = $('#address').val().trim();

                // проверяем не пустое ли поле город
                if (!city) return;

                // клонируем невидимое поле хранения
                $('div[name="address-element"]:first').clone(true).appendTo('#addresses-collection').removeClass('d-none');

                // заполняем адресные поля
                $('input[name="city[]"]:last').val(city); //$('#city').val()
                $('input[name="address[]"]:last').val(address); //$('#address').val()
                $('input[name="office[]"]:last').val($('#office').val().trim());
                // заполняем city place_id
                $('input[name="city_id[]"]:last').val($('#city').data('city_id'));

                // заполняем поле и комментарий типа адреса
                let addressTypeText = 'Основной адрес:';
                let addressType = 'primary';
                // поле primary должно быть одно - проверяем
                $('input[name="address_type[]"]').each(function () {
                    if (this.value == 'primary') {
                        addressTypeText = 'Дополнительный адрес:';
                        addressType = 'secondary';
                    }
                });
                $('small[name="address-type"]:last').text(addressTypeText);
                $('input[name="address_type[]"]:last').val(addressType);

                // параметры запроса координат
                //const countryOfficial = $('#country>option:selected').text();
                const countryG = $('#country').val();
                //const city_place_id = $('#city').data('city_id');

                // осуществляет геокодирование по адресу ЧОП
                const addressObj = {
                    country: countryG,
                    address: `${city} ${address}`
                }
                location.getCoordinatesByAddressPr(addressObj).then(locality => {
                    // присваиваем полям хранения широту и долготу
                    $('input[name="longitude[]"]:last').val(locality.lng);
                    $('input[name="latitude[]"]:last').val(locality.lat);

                    // добавляем кол-во городов
                    citiesQuantity++;
                    // очищаем поля ввода
                    $('div[name="city_input"] input').val('');

                    resolve();
                });
            });
        }

        // событие добавления строки с населенным пунктом
        let citiesQuantity = 0;
        $('#add_city').click(addAddress);

        // удаляет строку с населенным пунктом
        $('i[name="dell_city"]').click(function () {
            $(this).parent().parent().parent().remove();
            citiesQuantity--;
        });

        // загрузка формы данных организации на сервер
        $orgForm.submit(async function (e) {
            e.preventDefault();

            // проверяем есть ли введенные города
            if (citiesQuantity < 1) {
                // если хотя бы город в поле ввода введен, то добавляем его перед сохранением сами
                if ($('#city').val() != '') {
                    await addAddress();
                    // иначе выводим уведомление
                } else {
                    Interface.showInfo('#notification', 'Укажите город', '', 5000);
                    return;
                }
            }

            // включаем спинер
            $('#org_save_spinner').removeClass('d-none');

            const serialized = $('[form="chop_form"]', this).serialize();

            // отправляем запрос и обрабатываем ответ 
            $.post(PATHS.organizationDataLoadAPIurl, serialized, function (response) {
                // выводим статус
                if (response.code === 1) {
                    $('#organizationUpdateStatusOk').removeClass('d-none');
                    // формируем Ссылку на страницу ЧОП из ЛК
                    $('#go_page_nav_item, #go_page_nav_item2')
                        .removeClass('d-none').children('a').prop('href', '/agency/' + response.id);
                } else {
                    console.log(response);
                    $('#organizationUpdateStatusErr').removeClass('d-none');
                }
                // удаляем галочку через N секунд
                setTimeout(function () { $('#organizationUpdateStatusOk, #organizationUpdateStatusErr').addClass('d-none'); }, 5000);

                // выключаем спинер
                $('#org_save_spinner').addClass('d-none');
            }, 'json'); //, 'json'
        });

        // обработчик загрузки вкладки Организация с сервера перед открытием вкладки
        $('#nav-chop-description-tab').on('shown.bs.tab', function () {
            // если данные уже загружены - выходим
            if ($orgForm.data('loaded') == 'true') return;

            // получаем JSON c детальными данными организации
            $.getJSON(PATHS.getOrgPriceDataAPIurl, { get: 'detail_data' }, function (response) {

                if (response.code === 1) {
                    const data = response.data;

                    const spFolderPath = `/imgs/service_providers/${response.chopFolder}/`;

                    // формируем Ссылку на страницу ЧОП из ЛК
                    $('#go_page_nav_item, #go_page_nav_item2').removeClass('d-none').children('a').prop('href', '/agency/' + response.id);

                    // формируем страницу
                    // --- лого ---
                    if (response.chopFolder)
                        $('#chop_logo_img').get(0).src = spFolderPath + 'logo';

                    // --- нединамические поля ---
                    for (let field in data) {
                        $(`[name="${field}"]`, $orgForm).val(data[field]);
                    }

                    // --- сервисы ---
                    $('input', '#services_section, #signaling_options_s').toArray().forEach(el => {
                        el.checked = data[el.name] == 'on' ? true : null;
                    });
                    $pultSecCheck.trigger('onLoad'); //change

                    // --- фотографии ---
                    const photoCount = data.chop_photo_file_name.length;
                    // запоминаем сколько фото
                    $('#chop-photo-collection').data('count', photoCount);
                    let $element;
                    // формируем коллекцию
                    for (let i = 0; i < photoCount; i++) {
                        // создаем новый элемент
                        $element = $('div[name="chop-photo-element"]:first').clone(true).appendTo('#chop-photo-collection').removeClass('d-none');
                        // наполняем его
                        $('img', $element).get(0).src = spFolderPath + data.chop_photo_file_name[i];
                        $('input[name="chop_photo_file_name[]"]', $element).val(data.chop_photo_file_name[i]);
                        $('input[name="chop_photo_name[]"]', $element).val(data.chop_photo_name[i]);
                    }

                    // --- лицензии ---
                    const licenceCount = data.chop_licence_file_name.length;
                    // запоминаем сколько лицензий
                    $('#chop_licence_collection').data('count', licenceCount);

                    for (let i = 0; i < licenceCount; i++) {
                        // создаем новый элемент
                        $element = $('div[name="chop-licence-element"]:first').clone(true).appendTo('#chop_licence_collection').removeClass('d-none');
                        // наполняем его
                        $('img', $element).get(0).src = spFolderPath + data.chop_licence_file_name[i];
                        $('input[name="chop_licence_file_name[]"]', $element).val(data.chop_licence_file_name[i]);
                        $('input[name="chop_licence_name[]"]', $element).val(data.chop_licence_name[i]);
                    }

                    // --- отзывы ---
                    /*
                    const feedbackCount = data.feedback_file_name.length;
                    // запоминаем сколько отзывов
                    $('#chop_feedback_collection').data('count', feedbackCount);

                    for (let i = 0; i < feedbackCount; i++) {
                        // создаем новый элемент
                        $element = $('div[name="chop-feedback-element"]:first').clone(true).appendTo('#chop_feedback_collection').removeClass('d-none');
                        // наполняем его
                        $('input[name="feedback_file_name[]"]', $element).val(data.feedback_file_name[i]);
                        $('input[name="feedback_name[]"]', $element).val(data.feedback_name[i]);
                        $('input[name="feedback_client_name[]"]', $element).val(data.feedback_client_name[i]);
                        $('textarea[name="feedback_content[]"]', $element).val(data.feedback_content[i]);
                    }
                    */

                    // --- Почему Вы ---
                    const whyYouCount = data.why_you_item.length;
                    // запоминаем сколько клиентов
                    $('#why_list').data('count', whyYouCount);

                    for (let i = 0; i < whyYouCount; i++) {
                        // создаем новый элемент
                        $element = $('li[name="why-you-element"]:first').clone(true).appendTo('#why_list').removeClass('d-none');
                        // наполняем его
                        $('input[name="why_you_item[]"]', $element).val(data.why_you_item[i]);
                    }

                    // --- Звучные имена ---
                    const clientsCount = data.client_item.length;
                    // запоминаем сколько лицензий
                    $('#chop_clients_collection').data('count', clientsCount);

                    for (let i = 0; i < clientsCount; i++) {
                        // создаем новый элемент
                        $element = $('div[name="chop_client_element"]:first').clone(true).appendTo('#chop_clients_collection').removeClass('d-none');
                        // наполняем его
                        $('input[name="client_item[]"]', $element).val(data.client_item[i]);
                    }

                    // --- Адреса ---
                    // страна
                    $('#country option').each(function (i, el) {
                        if ($(this).text() == data.public_country) this.selected = true;
                        //val(data.country);
                    });

                    // адреса
                    const addressCount = data.city.length;
                    // запоминаем сколько адресов
                    citiesQuantity = addressCount;

                    for (let i = 0; i < addressCount; i++) {
                        // создаем новый элемент
                        $element = $('div[name="address-element"]:first').clone(true).appendTo('#addresses-collection').removeClass('d-none');
                        // наполняем его
                        $('input[name="city[]"]', $element).val(data.city[i]);
                        $('input[name="address[]"]', $element).val(data.address[i]);
                        $('input[name="office[]"]', $element).val(data.office[i]);
                        $('input[name="longitude[]"]', $element).val(data.longitude[i]);
                        $('input[name="latitude[]"]', $element).val(data.latitude[i]);
                        $('input[name="city_id[]"]', $element).val(data.city_id[i]);
                        $('input[name="address_type[]"]', $element).val(data.address_type[i]);
                        $('small[name="address-type"]', $element)
                            .text(data.address_type[i] == 'primary' ? 'Основной адрес' : 'Дополнительный адрес');
                    }

                    $orgForm.data('loaded', 'true');
                }
                if (response.code === 0) console.log(response.data);
                if (response.code === 2) $orgForm.data('loaded', 'true');
            });
        });
        // инициируем заполнение вкладки Организация
        $('#nav-chop-description-tab').trigger('shown.bs.tab');

        // инициируем автосохранение
        setInterval(() => {
            $orgForm.trigger('submit');
        }, 600000);

    }

}