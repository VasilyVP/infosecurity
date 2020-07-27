/** Класс управления маршрутизацией на клиенте */
import FL from '/js/modules/FuncsLib.js';

export default class Routing {
    constructor() {
        this.locality = {};
        this.who;
        this.search;
        this.page;
        this.url;
    }

    /** Формирует URL по переданным параметрам на страницу /search
     * locality - где ищем, who - юр. или физ. лица, params - объект с параметрами поиска {name, value: 'on'}
    */
    getSearchURL(locality, who = false, search = false, page = 1) {
        // инициируем исходный URL и добавляем страну
        let URL = location.origin + '/search/' + FL.translitStringRuToEn(locality.country.toLowerCase());

        // добавляем город и регион в закодированном виде 
        // ("Люберцы, Люберецкий район, Московская область" в "lyubertsy-lyuberetskiy_raion-moskovskaya_oblast")
        const urlStr = locality.full.toLowerCase().replace(/, /g, '~').replace(/ /g, '_');
        URL += '/' + FL.translitStringRuToEn(urlStr);

        URL += '?';

        let searchComp = [];
        if (who || search) {
            // добавляем параметры search
            if (search.length > 0) {
                searchComp = search.map(el => {
                    return (el.value == 'on') ? el.name : `${el.name}=${el.value}`
                });
                URL += searchComp.join('&');
                URL += '&';
            }
            // добавляем who
            if (who) URL += who + '&';
        }

        // добавляем page
        URL += 'page=' + page;

        this.url = encodeURI(URL);

        return this.url;
    }

    /** Разбирает адресную строку. Возвращает объект { locality, who, search } */
    parseSearchURL() {
        // получаем массив со страной и городом из URL
        const addr = location.pathname.split('/').slice(2);

        this.locality.country = addr[0];

        const full = addr[1].split('~');

        this.locality.city = full[0].replace('_', ' ');
        if (full.length === 2) this.locality.area = full[1].replace('_', ' ');
        if (full.length === 3) this.locality.region = full[2].replace('_', ' ');

        // получаем массив параметров поиска
        const searchArr = location.search.slice(1).split('&').map(el => {
            if (el.includes == '=') {
                const arr = el.split('=');
                return { name: arr[0], value: arr[1] };
            } else return el;
        });

        // получаем номер страницы, если он есть
        if (searchArr.some(el => { return el.includes('page=') })) this.page = searchArr.pop().slice(5);

        // если один параметр - показать все (all)
        if (searchArr.includes('service-type=all')) {
            this.search = searchArr;
            return this;
        }

        // получаем кто ищет (юрики или физики)
        this.who = searchArr.pop();
        // остается набор поисковых параметров
        this.search = searchArr;

        return this;
    }

}