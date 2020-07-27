import {PARAMS} from '/js/config.js';
import Routing from '/js/modules/Routing.js';

/** Модуль формирования страницы search */
export default class renderingSearch {
    constructor() {
        this.routing;
        this.currMap = {
            'рубль': 'руб',
            'гривна': 'грн',
            'евро': 'евро',
            'доллар': 'USD',
            'тенге': 'тнг',
            'манат': 'ман',
            'драм': 'драм',
            'лари': 'лари',
            'сом': 'сом',
            'лей': 'лей',
            'сомони': 'смн',
            'сум': 'сум'
        }
        
        // формируем соответствие параметру в search и элементу навигации
        this.servicesMap = {
            // виды услуг, по умолчанию Сигнализация service-type=signaling
            'service-type=signaling': {
                selector: 'select[name="service-type"] option[value="signaling"]',
                action: 'selected'
            },
            'CCTV': {
                selector: 'select[name="service-type"] option[value="CCTV"]',
                action: 'selected'
            },
            'service-type=CCTV': {
                selector: 'select[name="service-type"] option[value="CCTV"]',
                action: 'selected'
            },
            'guard': {
                selector: 'select[name="service-type"] option[value="guard"]',
                action: 'selected'
            },
            'service-type=guard': {
                selector: 'select[name="service-type"] option[value="guard"]',
                action: 'selected'
            },
            'GPS': {
                selector: 'select[name="service-type"] option[value="GPS"]',
                action: 'selected'
            },
            'service-type=GPS': {
                selector: 'select[name="service-type"] option[value="GPS"]',
                action: 'selected'
            },
            'cargo_escort': {
                selector: 'select[name="service-type"] option[value="cargo_escort"]',
                action: 'selected'
            },
            'service-type=cargo_escort': {
                selector: 'select[name="service-type"] option[value="cargo_escort"]',
                action: 'selected'
            },
            'collection': {
                selector: 'select[name="service-type"] option[value="collection"]',
                action: 'selected'
            },
            'service-type=collection': {
                selector: 'select[name="service-type"] option[value="collection"]',
                action: 'selected'
            },
            'service-type=access_control': {
                selector: 'select[name="service-type"] option[value="access_control"]',
                action: 'selected'
            },
            'service-type=maintenance': {
                selector: 'select[name="service-type"] option[value="maintenance"]',
                action: 'selected'
            },
            'design_installation': {
                selector: 'select[name="service-type"] option[value="design_installation"]',
                action: 'selected'
            },
            'service-type=design_installation': {
                selector: 'select[name="service-type"] option[value="design_installation"]',
                action: 'selected'
            },
            // вид сигнализации wired/wireless
            'connect-type=wired': {
                selector: 'select[name="connect-type"] option[value="wired"]',
                action: 'selected'
            },
            'connect-type=wireless': {
                selector: 'select[name="connect-type"] option[value="wireless"]',
                action: 'selected'
            },
            'connect-type=no_matter': {
                selector: 'select[name="connect-type"] option[value="no_matter"]',
                action: 'selected'
            },
            // местоположение сигнализации
            'signaling_flat': {
                selector: 'select[name="signaling-place-type"] option[value="flat"]',
                action: 'selected'
            },
            'signaling-place-type=flat': {
                selector: 'select[name="signaling-place-type"] option[value="flat"]',
                action: 'selected'
            },
            'signaling_house': {
                selector: 'select[name="signaling-place-type"] option[value="house"]',
                action: 'selected'
            },
            'signaling-place-type=house': {
                selector: 'select[name="signaling-place-type"] option[value="house"]',
                action: 'selected'
            },
            'signaling_garage': {
                selector: 'select[name="signaling-place-type"] option[value="garage"]',
                action: 'selected'
            },
            'signaling-place-type=garage': {
                selector: 'select[name="signaling-place-type"] option[value="garage"]',
                action: 'selected'
            },
            // опции сигнализации
            'sec_signaling': {
                selector: 'input[name="sec_signaling"]',
                action: 'checked'
            },
            'fire_signaling': {
                selector: 'input[name="fire_signaling"]',
                action: 'checked'
            },
            'alarm_button': {
                selector: 'input[name="alarm_button"]',
                action: 'checked'
            },
            'water_leak': {
                selector: 'input[name="water_leak"]',
                action: 'checked'
            },
            'gas_leak': {
                selector: 'input[name="gas_leak"]',
                action: 'checked'
            },
            'glass_break': {
                selector: 'input[name="glass_break"]',
                action: 'checked'
            },
            // местоположение видеонаблюдения
            'CCTV-place-type=flat': {
                selector: 'select[name="CCTV-place-type"] option[value="flat"]',
                action: 'selected'
            },
            'CCTV-place-type=house': {
                selector: 'select[name="CCTV-place-type"] option[value="house"]',
                action: 'selected'
            },
            'CCTV-place-type=entrance': {
                selector: 'select[name="CCTV-place-type"] option[value="entrance"]',
                action: 'selected'
            },
            'CCTV-place-type=office': {
                selector: 'select[name="CCTV-place-type"] option[value="office"]',
                action: 'selected'
            },
            'CCTV-place-type=shop': {
                selector: 'select[name="CCTV-place-type"] option[value="shop"]',
                action: 'selected'
            },
            'CCTV-place-type=warehouse': {
                selector: 'select[name="CCTV-place-type"] option[value="warehouse"]',
                action: 'selected'
            },
            'CCTV-place-type=car_wash': {
                selector: 'select[name="CCTV-place-type"] option[value="car_wash"]',
                action: 'selected'
            },
            // виды физохраны
            'guard-armed=unarmed': {
                selector: 'select[name="guard-armed"] option[value="unarmed"]',
                action: 'selected'
            },
            'guard-armed=armed': {
                selector: 'select[name="guard-armed"] option[value="armed"]',
                action: 'selected'
            },
            // режим охраны
            'guard-mode=dayly': {
                selector: 'select[name="guard-mode"] option[value="dayly"]',
                action: 'selected'
            },
            'guard-mode=nightly': {
                selector: 'select[name="guard-mode"] option[value="nightly"]',
                action: 'selected'
            },
            'guard-mode=24': {
                selector: 'select[name="guard-mode"] option[value="24"]',
                action: 'selected'
            },
            // вид сопровождения грузов
            'cargo-escort-armed=unarmed': {
                selector: 'select[name="cargo-escort-armed"] option[value="unarmed"]',
                action: 'selected'
            },
            'cargo-escort-armed=armed': {
                selector: 'select[name="cargo-escort-armed"] option[value="armed"]',
                action: 'selected'
            },
        }
    }

    /** Формирует визуализацию параметров в зависимости от выбираемых видов услуг
     * функция обработчика события change для <select>
     */
    orgPhysParamsShow() {
        // устанавливаем контекст (физики или юрики)
        const $context = $(this).parents('div[role="tabpanel"]');
        switch (this.value) {
            case 'signaling':
                $('[data-parameter="signaling"]', $context).removeClass('d-none');
                $('[data-parameter][data-parameter!="signaling"]:not(select[data-parameter="service-type"])', $context).addClass('d-none');
                break;
            case 'CCTV':
                $('[data-parameter="CCTV"]', $context).removeClass('d-none');
                $('[data-parameter][data-parameter!="CCTV"]:not(select[data-parameter="service-type"])', $context).addClass('d-none');
                break;
            case 'guard':
                $('[data-parameter="guard"]', $context).removeClass('d-none');
                $('[data-parameter][data-parameter!="guard"]:not(select[data-parameter="service-type"])', $context).addClass('d-none');
                break;
            case 'cargo_escort':
                $('[data-parameter="cargo-escort"]', $context).removeClass('d-none');
                $('[data-parameter][data-parameter!="cargo-escort"]:not(select[data-parameter="service-type"])', $context).addClass('d-none');
                break;
            default:
                $('[data-parameter]:not(select[data-parameter="service-type"])', $context).addClass('d-none');
                break;
        }
    }

    /** Устанавливает параметры поиска из параметров в адресной строке */
    setSearchParameters() {
        // разбираем адресную строку
        const params = new Routing().parseSearchURL();

        // сохраняем объект Routing
        this.routing = params;
        // сохраняем объект mapping сервисов - параметров search
        const map = this.servicesMap;

        // формируем контекст - организации/физики
        const $context = $('#' + params.who);

        // если юрики - активируем соответствующую вкладку (по умолчанию физики)
        if (params.who == 'nav-org') $('a[href="#nav-org"]').tab('show');
        
        // выставляем параметры
        for(let param of params.search) {
            if (map.hasOwnProperty(param)) $(map[param].selector, $context).prop(map[param].action, true);
        }

        // активируем интерфейсы переклчателей
        $(".m_switch_check:checkbox").mSwitch();
        // показываем панель параметров поиска
        $('#nav-tabParams').removeClass('d-none');
    }

    /** Устанавливает страну и полный адрес */
    setFullAddrCountry() {
        // берем страну и город из определения по IP (если со страницы main, то не определяет по IP)
        let country = $('#country').data('country') || false;
        let city = $('#city').data('city') || false;

        // если нет города, проверяем что есть в хранилище и подставляем
        if (!city && (sessionStorage.location || false)) {
            const location = JSON.parse(sessionStorage.location);
            country = location.country || false;
            city = location.full || false;
        }
        // заполняем поле города
        if (country) $(`#country option:contains(${country})`).prop('selected', true);
        if (city) $('#city').val(city);
    }

    /** Выводим результаты поиска (карточки ЧОП) */
    showSearchResults(providers, cardCaption, search) {
        // выводим карточки
        for (let provider of providers) {
            // клонируем карточку 
            const chop = $('div[name="chop-template"]').clone(true).appendTo('#search-results').removeClass('d-none').attr('name', 'chop').get(0);
            // заполняем карточку
            $('h2[name="name"]', chop).text(provider.name);
            $('address[name="city"]', chop).text(provider.city);
            $('address[name="address"]', chop).text(provider.address);
            $('button[name="show_phone"]', chop).data('phone', provider.phone);
            // если зарегистрированный ЧОП, то добавляем ссылку на страницу ЧОП и при наличии добавляем лого
            if (provider.folder || false) {
                $('a[name="gotopage"]', chop).prop('href', '/agency/' + provider.provider_link);
                if (provider.logo_flag == 'true') {
                    $('img[name="logo"]', chop)
                        .prop('src', '/imgs/service_providers/' + provider.folder + '/logo')
                        .prop('alt', provider.name);
                }
            } else {
                $('a[name="gotopage"]', chop).removeClass('btn-outline-dark').addClass('btn-light').addClass('disabled')
                                                .addClass('text-muted');
            }
            // заполняем название услуги в карточке
            $('h3[name="cardCaption"]', chop).text(cardCaption);
            // заполняем стоимости
            // если есть ненулевая цена или нулевая цена с ненулевой абоненткой
            if (provider.price || (provider.price === 0 && provider.maintenance !== 0)) {
                if (provider.maintenance) {
                    $('small[name="offer-price-title"]', chop).text('Подключение от ');
                    $('small[name="offer-maintenance-title"]', chop).text('Абонентская плата от ');
                    $('span[name="offer-maintenance"]', chop).text(provider.maintenance);
                    $('span[name="offer-maintenance-currency"]', chop).text(this.currMap[provider.currency]);
                } else {
                    $('small[name="offer-price-title"]', chop).text('Стоимость от ');
                    $('div[name="maintenance-section"]', chop).addClass('d-none');
                    $('div[name="price-section"]', chop).addClass('offset-md-4');
                }
                $('span[name="offer-price"]', chop).text(provider.price);
                // добавляем "в час" для Физохраны
                let per = '';
                //if (cardCaption.includes('охрана')) per = '/час';
                if (search[0].value == 'guard') per = '/час';

                $('span[name="offer-price-currency"]', chop).text(this.currMap[provider.currency] + per);
            } else {
                $('small[name="offer-maintenance-title"]', chop).text('Цена по запросу');
            }
            // формируем спецификацию
            if (provider.specification && provider.specification.length) {
                const $tbody = $('tbody', chop);
                for (let string of provider.specification) {
                    const $specStr = $('tr[name="spec-string"]:first', chop).clone().appendTo($tbody).removeClass('d-none');
                    $('td', $specStr).each((i, el) => {
                        // преобразовываем в число числовые поля (чтобы false не отображалось)
                        const str = i ? +string[i] : string[i];
                        $(el).text(str);
                    });
                }
                $('div[name="show-spec-section"]', chop).removeClass('d-none');
            }
        }
    }

    /** Выводит страничную навигацию*/
    showPagesNav(pages, quantity, currentPage, thisObj) {
        const thisModuleObj = this;

        if (pages > 1) {
            const $pageForward = $('#page-forward');
            const $pageBack = $('#page-back');
            const $pageBtn = $('li[name="page-btn-template"]');

            for (let i = 1, c = Math.min(PARAMS.MAX_PAGES, pages); i <= c; i++) { // 22
                // вставляем новую кнопку страницы
                const $newPage = $pageBtn.clone().insertBefore($pageForward).attr('name', 'page').removeClass('d-none');                
                
                // вместо 21й страницы добавляем ...
                /*
                if (i == 21) {
                    $('a', $newPage).text('...');
                    $newPage.addClass('disabled');
                } else {
                    */
                    // формируем ссылку для кнопки (для роботов)
                  //  const href = location.href.replace(/page=\d/, 'page=' + i);
                    // оформляем ссылку в кнопке
                    $('a', $newPage).text(i).data('page', i).attr('name', 'apage');//.attr('href', href);
                //}
                
                // добавляем признак текущей страницы
                if (currentPage == i) $newPage.addClass('active');
            }

            // добавляем обработчик нажатия кнопки страницы - 
            // инициация события Показать результаты и передаем туда номер страницы и количество записей
            $('a[name="apage"]').click(function (e) { //.page-link
                e.preventDefault();
                
                const page = $(this).data('page');

                // проверка на максимальную страницу
                if (page > PARAMS.MAX_PAGES) return;
                
                thisModuleObj.routing.page = page;

                $(thisObj).trigger('click', [page, quantity]);
            });

            // обработчик кнопки назад
            $pageBack.off(); // удаляем старый обработчик
            $pageBack.on('click', function (e) {
                if (currentPage == 1) return;
                $(thisObj).trigger('click', [Number(currentPage) - 1, quantity]);
            });
            // обработчик кнопки вперед
            $pageForward.off(); // удаляем старый обработчик
            $pageForward.on('click', function (e) {
                if (currentPage == pages || currentPage == PARAMS.MAX_PAGES) return;
                $(thisObj).trigger('click', [Number(currentPage) + 1, quantity]);
            });

            // если последняя страница - блокируем "Вперед"
            if ((currentPage == pages) || (currentPage == PARAMS.MAX_PAGES)) $pageForward.addClass('disabled');
            else $pageForward.removeClass('disabled');
            // если первая - блокируется назад
            if (currentPage == 1) $pageBack.addClass('disabled');
            else $pageBack.removeClass('disabled');
            // показываем навигацию
            $('#page-nav').removeClass('d-none');
            // скрываем навигацию
        } else $('#page-nav').addClass('d-none');

    }

    /** Очищаем результаты (Карточки, заголовок, кнопки страниц) */
    clearResults() {
        // удаляем существующие карточки
        $('div[name="chop"]').remove();
        // удаляем старые кнопки страниц
        $('li[name="page"]').remove();
        // очищаем заголовок
        $('#caption').text('');
        // скрываем область страниц
        $('#page-nav').addClass('d-none');
        // скрываем "Ничего не нашли"
        $('#find_nothing').addClass('d-none');
    }

}