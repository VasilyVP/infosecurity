// логика закладки "Страницы ЧОП"
import { PATHS, KEYS, USE_SESSION_TOKEN } from '/js/config.js';
// подключаем автодополнение ЧОП
import ClientComplite from '/js/modules/ClientComplite.js';
import CityComplite from '/js/modules/CityComplite.js';

export default class PagesTab {
    constructor() {
        // инициируем автодополнение страниц ЧОП
        const clients = new ClientComplite({
            completionID: 'search_chop_field',
            pageStatusID: 'page_status',
            cityID: 'search_chop_city',
            useCity: 'use_city'
        });

        // инициируем автодополнение городов
        const city = new CityComplite({
            completionID: 'search_chop_city',
            pageStatusID: 'page_status'
        });

        const $ok = $('i[name="ok"]');
        const $no = $('i[name="no"]');

        // обработчик фокуса поля поиска ЧОП - очищает список
        $('#search_chop_field').focus(e => {
            $('div[data-function="userInfo"]').addClass('d-none');
            $ok.add($no).addClass('d-none');
        });

        // обработчи выбора подсказки - выводит ЧОП
        $('#search_chop_field').on('suggestion', function (event, inputData) {
            $('input[name="clientID"]').val(inputData.id);
            const $name = $('input[name="name"]').val(inputData.name);
            const $email = $('input[name="email"]').val(inputData.email);
            $('input[name="city"]').val(inputData.city);
            $('select[name="active"]').val(inputData.active);
            $('div[data-function="userInfo"]').removeClass('d-none');

            // добавляем ссылку на страницу ЧОПа
            /*
            if (inputData.uid) {
                $('a[name="link-to-page"]').prop('href', location.origin + '/agency/' + inputData.uid);
                $name.addClass('text-primary').css('cursor', 'pointer');
            }
            */

            // добавляем ссылку на страницу ЧОПа и в ЛК ЧОПа
            const $linkToPage = $('a[name="link-to-page"]');
            const $aLinkToPage = $('a[name="link-to-pa-page"]');
            const pageStatus = $('#page_status').val();
            // функция добавления куки userLogin
            function addUserLoginCookie(e) {
                document.cookie = `userLogin=${$email.val()};max-age=3600;`;
            }
            // проверяем наличие uid и что registered
            if (inputData.uid && pageStatus === 'registered') {
                // добавляем свойства в ссылку и убираем обработчки события (return false), если есть
                $linkToPage.prop('href', location.origin + '/agency/' + inputData.uid).off();
                $name.addClass('text-primary').css('cursor', 'pointer');

                $aLinkToPage.prop('href', location.origin + '/personal_area').off();
                $email.addClass('text-primary').css('cursor', 'pointer');

                // пишем куки с user email при клике по ссылке
                $aLinkToPage.click(addUserLoginCookie);
            } else {
                $linkToPage.prop('href', '#').click(() => {return false});
                $name.removeClass('text-primary').css('cursor', 'auto');

                $aLinkToPage.prop('href', '#').click(() => {return false});
                $email.removeClass('text-primary').css('cursor', 'auto');

                // убираем обработчик добавления куки
                $aLinkToPage.off('click', '', addUserLoginCookie);
            }
        });

        // обработчик кнопки Сохранить (статус страницы ЧОП)
        const $pagesForm = $('#pages_form');
        $pagesForm.submit(function (e) {
            e.preventDefault();
            const data = {
                clientID: $('input[name="clientID"]', $(this)).val(),
                active: $('select[name="active"]', $(this)).val(),
                status: $('#page_status').val(),
                email: $('input[name="email"]', $(this)).val().trim()
            }
            $.post(PATHS.setClientStatusAPIurl, data, response => {
                // выводим уведомление о результате
                if (response.code == 1) {
                    $no.addClass('d-none');
                    $ok.removeClass('d-none');
                    setTimeout(() => { $ok.addClass('d-none') }, 5000);
                } else {
                    $no.removeClass('d-none');
                    $ok.addClass('d-none');
                    setTimeout(() => { $no.addClass('d-none') }, 5000);
                }
            }, 'json');
        });

    }

}