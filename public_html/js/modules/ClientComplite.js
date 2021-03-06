// Модуль автозавершения наименования клиентов по Названию/email
import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';

export default class ClientComplite {
    // инициируем typeahead
    constructor(params) {
        this.pageStatusID = params.pageStatusID;
        this.cityID = params.cityID;
        this.useCity = params.useCity;

        // автозавершение работает в элементе с классом .typeahead и id
        $('.typeahead' + '#' + params.completionID).typeahead(
            {
                hint: false, // true does not work - это подсказки в поле ввода
                highlight: true,
                minLength: 1
            },
            {
                templates: {
                    //    footer: '<img src="imgs/logos/powered_by_google_on_white.png" style="float:right;">'
                },
                limit: 10, // 5 is default
                source: this.getMatches(),

                // определяем отображение подсказок
                display: function (obj) {
                    let suggestion;
                    
                    if (obj.name !== undefined) {
                        suggestion = `${obj.name}, ${obj.city}, ${obj.phone}, ${obj.email}`;
                    } else suggestion = '';

                    return suggestion;                    
                }
            }
        );

        // инициируем обработчик на выбор подсказки. Запоминаем значения полей в объекте
        $('.typeahead' + '#' + params.completionID).on('typeahead:select', (ev, suggestion) => {
            // инициируем событие получения позиции из подсказки
            $('.typeahead' + '#' + params.completionID).trigger('suggestion', suggestion);
        });
    }

    // вызывается typeahead, делает ajax запрос совпадений и возвращает их
    getMatches() {
        const thisObj = this;
        return function findMatches(query, cb1, cb2) {
            // запоминаем статус страницы ЧОП
            const pageStatus = $(`#${thisObj.pageStatusID}`).val();
            const useCity = $(`#${thisObj.useCity}`).prop('checked');
            const city = $(`#${thisObj.cityID}`).val();

            // запрос JSON на подсказки
            $.getJSON(PATHS.getClientsListAPIurl, 
                { query: query, status: pageStatus, city: city, useCity: useCity }, 
                response => {
                // возвращаем подсказки при асинхронном запросе. cb1 - для синхронного
                if (response.code === 1) {
                    response.data.push(''); // это убирает глюк typeahead
                    cb2(response.data);
                }
            }
            );
        };
    }

}