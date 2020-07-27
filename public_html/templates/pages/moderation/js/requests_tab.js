/** Класс обработки логики закладки "Запросы" */
import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';
import InterfaceLib from '/js/modules/InterfaceLib.js';

export default class requestsTab {

    constructor() {
        const thisObj = this;
        // добавляем обработчик событий для кнопок Утвердить/Отклонить
        $('button[name="approve"]').click(this.clickApprove);
        $('button[name="decline"]').click(this.clickDecline);
        // обработчик на кнопку Обновить
        $('#refresh_btn').click(function () {
            $('div[name="request_row"]:visible').remove();
            thisObj.loadRequests();
        });

        // загружаем список запросов
        this.loadRequests();
    }

    loadRequests() {
        $.getJSON(PATHS.providerRequestsAPIurl, { request: 'list' }, response => {
            if (response.code === 1) {
                const $requestsList = $('#requests_list')
                const $requestRow = $('[name="request_row"]:first', $requestsList);

                const data = response.data;
                // обновляем информер количества запросов
                $('#requests_quantity').text(data.count);

                if (data.requests.length > 0) {
                    $('#request_status').addClass('d-none');
                    // выводим список ЧОП
                    data.requests.forEach(el => {
                        const row = $requestRow.clone(true).appendTo($requestsList).removeClass('d-none').get(0);
                        $('a', row).prop('href', location.origin + '/agency/' + el.id).text(el.name);
                        $('[name="city"]', row).text(el.city);
                        $('[name="updated"]', row).text(el.updated);
                        $('button', row).data('id', el.id).data('userName', el.userName).data('userPatronymic', el.userPatronymic)
                            .data('email', el.email).data('orgName', el.name);
                    });
                } else {
                    $('#request_status').removeClass('d-none');
                }
            } else {
                console.log(response);
            }
        })
    }

    clickApprove() {
        const $this = $(this);
        const id = $this.data('id');
        const name = $this.data('userName');
        const patronymic = $this.data('userPatronymic');
        const fullName = name + (patronymic ? (' ' + patronymic) : '');
        const email = $this.data('email');
        const orgName = $this.data('orgName');
        
        const $reqRow = $this.parents('div[name="request_row"]');
        const comment = $('textarea', $reqRow).val();
    
        $.getJSON(PATHS.providerRequestsAPIurl,
            {
                request: 'approve',
                id: id,
                name: fullName,
                email: email,
                orgName: orgName,
                comment: comment
            }, response => 
            {// обработчик ответа
                const $update = $(this).nextAll('i[name="showUpdate"]');
                // если все Ок
                if (response.code === 1) {
                    // выводим уведомление, что все Ок                
                    InterfaceLib.showStatus($update, 'yes', 0);
                } else {
                    InterfaceLib.showStatus($update, 'no');
                    console.log(response);
                }
            });
    }

    clickDecline() {
        const $this = $(this);
        const id = $this.data('id');
        const name = $this.data('userName');
        const patronymic = $this.data('userPatronymic');
        const fullName = name + (patronymic ? (' ' + patronymic) : '');
        const email = $this.data('email');
        const orgName = $this.data('orgName');

        const $reqRow = $this.parents('div[name="request_row"]');
        const comment = $('textarea', $reqRow).val();

        $.getJSON(PATHS.providerRequestsAPIurl,
            {
                request: 'decline',
                id: id,
                name: fullName,
                email: email,
                orgName: orgName,
                comment: comment
            }, response => {
            const $update = $(this).nextAll('i[name="showUpdate"]');
            // если все Ок
            if (response.code === 1) {
                // выводим уведомление, что все Ок                
                InterfaceLib.showStatus($update, 'yes', 0);
            } else {
                InterfaceLib.showStatus($update, 'no');
                console.log(response);
            }
        });
    }

}