// Модуль автозавершения населенного пункта через сервис VK
export default class PlaceAutoCompliteVK {
    // инициирует typeahead и страны
    constructor(locality, country) {
        // запоминаем поле ввода страны
        this.country_field = country;

        // соответствие Стран и country_id у VK API
        this.countries = {
            'Россия': 1,
            'Украина': 2,
            'Беларусь': 3,
            'Казахстан': 4,
            'Азербайджан': 5,
            'Армения': 6,
            'Грузия': 7,
            'Киргизия': 11,
            'Молдова': 15,
            'Таджикистан': 16,
            'Туркменистан': 17,
            'Узбекистан': 18
        };

        // автозавершение работает в элементе с классом .typeahead
        $('.typeahead').typeahead({
            hint: false, // true does not work - это подсказки в поле ввода
            highlight: true,
            minLength: 1
        },
        {
            limit: 5, // 5 is default
            source: this.getMatches(),
            // определяем порядок отображения подсказок. suggestionObj - ппц объект по два набора на подсказку
            display: function (suggestionObj) {
                if (Object.keys(suggestionObj).some(key => key == '_query'))
                    return Object.values(suggestionObj).slice(2).join(', ');
                else
                    return Object.values(suggestionObj).slice(1).join(', ');
            }
        });

        // инициируем обработчик на выбор подсказки. Запоминаем значения полей в объекте
        $('.typeahead').on('typeahead:select', (ev, suggestion) => {
            console.log('From geo-positioning: ' + Object.values(locality).join(', '));
            
            locality.city = suggestion.title;
            locality.area = suggestion.area;
            locality.region = suggestion.region;
            locality.country = $('#' + country).val();

            console.log('From input: ' + Object.values(locality).join(', '));
        });
    }

    // вызывается typeahead, делает ajax запрос совпадений и возвращает их
    getMatches() {
        // запоминаем значение this объекта
        const countries = this.countries;
        const country_field = this.country_field;

        return function findMatches(query, cb, cb2) {
            // получаем countryID выбранной страны
            const country = $('#' + country_field).val();
            const countryID = countries[country];

            // параметры VK getCities
            const data = {
                code: 'RU',
                count: 6,
                country_id: countryID,
                access_token: PATHS.suggestionAPItokenVK,
                v: '5.76',
                q: query
            };
            // параметры ajax JSONP
            const params = {
                url: PATHS.suggestionAPIurlVK,
                jsonp: 'callback',
                dataType: 'jsonp',
                data: data
            };
            // обработчик ответа ajax от VK
            params.success = function (response) {
                cb2(response.response.items);
            };
            // вызов ajax
            $.ajax(params);
        };
    }

}
