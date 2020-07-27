// закладка обработки прейскуранта
import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';

export default class PriceTab {
    constructor() {
        const $wiredCheck = $('#wired_check');
        const $wirelessCheck = $('#wireless_check');
        const $services = $('#pult_security_check, #physic_security_check, #cctv_check, #gps_check, #gas_check, #water_check');
        // управляем переключением видимости секций прейскуранта в зависимости от чекбоксов видов услуг
        $services.change(function (e) { // , priceCheck trigger - признак, что вызвано через тригер
            // соответствие id чекбокса и раздела таблицы
            const map = {
                pult_security_check: 'price_signal_tbl',
                physic_security_check: 'price_phys_sec_tbl',
                cctv_check: 'price_cctv_tbl',
                gps_check: 'price_gps_tbl',
                gas_check: 'gas_detection_ops',
                water_check: 'water_detection_ops'
            };

            let quantityVisible = 0;
            $services.toArray().forEach(el => {
                if (el.checked) {
                    $(`#${map[el.id]}`).removeClass('d-none');
                    quantityVisible++;
                } else $(`#${map[el.id]}`).addClass('d-none');
            });

            // если все разделы таблицы невидимы, то и валюта, кнопка и легенда скрываются
            const $hidedSections = $('#price_currency_section, #price_save_section, #price_legend');
            if (quantityVisible === 0) {
                $hidedSections.addClass('d-none');
            } else {
                $hidedSections.removeClass('d-none');
            }
        });

        // управляем переключением видимости Проводная/Беспроводная
        $wiredCheck.change(function () {
            if (this.checked) $('#price_signal_tbl [type="wired"]').removeClass('d-none');
            else $('#price_signal_tbl [type="wired"]').addClass('d-none');
        });
        $wirelessCheck.change(function () {
            if (this.checked) $('#price_signal_tbl [type="wireless"]').removeClass('d-none');
            else $('#price_signal_tbl [type="wireless"]').addClass('d-none');
        });

        const $priceForm = $('#price_form');
        // загрузка формы прейскуранта
        $priceForm.submit(e => {
            e.preventDefault();
            // включаем спинер
            $('#price_save_spinner').removeClass('d-none');

            const serialized = $('input, select', '#price_form').serialize();

            // отправляем запрос и обрабатываем ответ 
            $.post(PATHS.priceDataLoadAPIurl, serialized, function (response) {
                // выводим статус
                if (response.code === 1) $('#priceUpdateStatusOk').removeClass('d-none');
                else {
                    console.log(response);
                    $('#priceUpdateStatusErr').removeClass('d-none');
                }
                // удаляем галочку через N секунд
                setTimeout(function () { $('#priceUpdateStatusOk, #priceUpdateStatusErr').addClass('d-none'); }, 5000);

                // выключаем спинер
                $('#price_save_spinner').addClass('d-none');
            }, 'json');
        });

        // обработчик загрузки прейскуранта с сервера перед открытием вкладки
        $('#nav-price-tab').on('shown.bs.tab', function () {
            // если данные уже загружены - выходим
            if ($priceForm.data('loaded') == 'true') return;

            // формируем отображение разделов прейскуранта
            $services.trigger('change', true);
            $wiredCheck.trigger('change', true);
            $wirelessCheck.trigger('change', true);

            // получаем JSON c Прейскурантом
            $.getJSON(PATHS.getOrgPriceDataAPIurl, { get: 'price' }, function (response) {
                if (response.code === 1) {
                    const price = JSON.parse(response.data);
                    // заполняем таблицу значениями Прейскуранта
                    $('select, input', '#price_form').toArray().forEach(el => {
                        el.value = (price[el.name] !== undefined) ? price[el.name] : null;// || null;
                    });

                    $priceForm.data('loaded', 'true');
                }
                if (response.code === 0) console.log(response.data);
                if (response.code === 2) $priceForm.data('loaded', 'true');
            });
        });

        // инициируем автосохранение
        setInterval(() => {
            $priceForm.trigger('submit');
        }, 600000);

    }

}