// Модуль автозавершения населенного пункта через сервис Google Places по стране country
export default class PlaceAutoCompliteG {
    // инициирует typeahead и страны
    constructor(params) {
        // 
        this.PATHS = params.PATHS;
        
        // определяет координаты в районе которых предлагать подсказки
        this.lookingAround = {}; // obj: {latitude, longitude}

        // запоминаем поле ввода страны
        this.countryID = params.countryID;
        // тип запроса
        this.require = params.require;
        // id поля автозавершения
        this.completionID = params.completionID;
        // агрегатор местоположения
        this.location = {};
        // запоминаем this
        const thisObj = this;
        // формируем session_token для G P Autocomplite
        const arr = new Array(10).fill(0);
        if (params.USE_SESSION_TOKEN) this.session_token = arr.map(() => { return Math.floor(Math.random() * 10); }).join('');

        // соответствие Стран и country_id у G API
        this.countries = {
            'Россия': 'ru',
            'Украина': 'ua',
            'Беларусь': 'by',
            'Казахстан': 'kz',
            'Эстония': 'ee',
            'Латвия': 'lv',
            'Литва': 'lt',
            'Азербайджан': 'az',
            'Армения': 'am',
            'Грузия': 'ge',
            'Киргизия': 'kg',
            'Молдова': 'md',
            'Таджикистан': 'tj',
            'Туркменистан': 'tm',
            'Узбекистан': 'uz'
        };

        // автозавершение работает в элементе с классом .typeahead
        $('.typeahead' + '#' + this.completionID).typeahead({
            hint: false, // true does not work - это подсказки в поле ввода
            highlight: true,
            minLength: 1
        },
            {
                templates: {
                    footer: '<img src="/imgs/logos/powered_by_google_on_white.png" style="float:right;">'
                },
                limit: 5, // 5 is default
                source: this.getMatches(),

                // определяем отображение подсказок (suggestionObj - ппц объект по два набора на подсказку)
                display: function (suggestionObj) {
                    // запоминаем массив подсказок
                    let terms = suggestionObj.terms || [];
                    // обрезаем в подсказке страну и город(если улицу подсказываем), не все обрезает в длинных подсказках
                    let cutIndex;
                    let suggestion = '';

                    //console.log(suggestionObj);

                    if (thisObj.require == 'city')
                        if (terms.length > 1) {
                            cutIndex = -1;
                            suggestion = terms.map(el => { return el.value }).slice(0, cutIndex).join(', ');
                        } else if (terms.length == 1) suggestion = terms[0].value;

                    if (thisObj.require == 'address') {
                        suggestion = suggestionObj.structured_formatting ? suggestionObj.structured_formatting.main_text : '';
                    }

                    /*
                    // если больше одного элемента
                    if (terms.length > 1) {
                        // убираем лишние поля в подсказках
                        // если город, то только страну убираем, если адрес, то еще стараемся убрать город (еще одну подсказку)
                        if (thisObj.require == 'city') cutIndex = -1;
                        if (thisObj.require == 'address')
                            if (terms.length == 5) cutIndex = -3;
                            else if (terms.length > 2) cutIndex = -2;
                            else cutIndex = -1;
                        // собираем элементы подсказки в массиве и удаляем страну и город(если адрес)
                        suggestion = terms.map(el => { return el.value }).slice(0, cutIndex).join(', ');
                    } // если один элемент, то ничего не обрезаем
                    else if (terms.length == 1) suggestion = terms[0].value;
                    // suggestion = terms.map(el => el.value).join(', ');
                    */
                    return suggestion;
                }
            });

        // инициируем обработчик на выбор подсказки. Запоминаем значения полей в объекте
        $('.typeahead' + '#' + this.completionID).on('typeahead:select', (ev, suggestion) => {
            // запоминаем поля            
            const location = thisObj.location;
            const terms = suggestion.terms;

            //очищаем поля от предыдущего ввода
            location.place_id = null;
            location.country = null;
            location.region = null;
            location.area = null;
            location.city = null;

            // запоминаем в location поля place_id
            location.place_id = suggestion.place_id;

            // и region, area, city
            if (thisObj.require == 'city') {
                switch (terms.length) {
                    case 4:
                        location.region = terms[2].value;
                        location.area = terms[1].value;
                        location.city = terms[0].value;
                        break;
                    case 3:
                        location.region = terms[1].value;
                        location.city = terms[0].value;
                        break;
                    case 2:
                        location.city = terms[0].value;
                        break;
                    case 1:
                        location.city = terms[0].value;
                        break;
                }
            }

            suggestion.terms.forEach((el, i) => {
                //console.log(`Suggestion.terms[${i}]: ` + el.value);
            });

            // инициируем событие получения позиции из подсказки
            $('#' + thisObj.completionID).trigger('suggestion', thisObj.location);
        });
    }

    // вызывается typeahead, делает ajax запрос совпадений и возвращает их
    getMatches() {
        // запоминаем значения объектов
        const countries = this.countries;
        const require = this.require;
        const thisObj = this;
        // $ поле ввода страны
        const $country = $('#' + this.countryID);

        return function findMatches(query, cb1, cb2) {
            const country = $country.val();
            // получаем географический код выбранной страны
            const countryCode = countries[country];

            let latitude = thisObj.lookingAround.latitude || false;
            let longitude = thisObj.lookingAround.longitude || false;
            //console.log('looking around in the suggestion: ' + latitude + ', ' + longitude);

            // запрос JSON на подсказки
            $.getJSON(
                thisObj.PATHS.suggestionAPIurlG,
                {
                    input: query,
                    country: countryCode,
                    require: require,
                    session_token: thisObj.session_token,
                    location: thisObj.lookingAround
                },
                response => {
                    let matches = response.predictions;

                    // фильтры на подсказки улиц (левые города и дубли улиц)
                    if (thisObj.require == 'address') {

                        // фильтруем подсказки не из искомых городов
                        /*
                        matches = matches.filter(el => {
                            // если в description есть наш город
                            return el.description.toLowerCase().includes(thisObj.location.city.toLowerCase()); // + ','
                        });
                        */
                        // фильтруем дубли улиц
                        let values = [];
                        matches = matches.filter(el => {
                            if (values.includes(el.terms[0].value)) return false;
                            else {
                                values.push(el.terms[0].value);
                                return true;
                            }
                        });
                    }
                    // костыли, чтобы все подсказки отображались из-за бага в typeahead
                    if (matches.length > 0) matches.push('');
                    if (matches.length == 5) matches.push('');
                    // возвращаем подсказки при асинхронном запросе. cb1 - для синхронного
                    cb2(matches);
                }
            );
        };
    }

}
