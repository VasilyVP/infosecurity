// модуль выделения элементов навигации в меню при скроллинге
export default class ActiveNavItems {
    // параметры: селектор с секциями, селектор элементов меню, класс изменения выделения
    constructor(sections, navItem, activeClass) {
        // берем элементы с классами 
        const $headers = $(sections);
        // при прокручивании
        $(window).scroll(event => {
            // если header элемент в зоне видимости scrollY + innerHight/4
            // убираем подсветку всех элементов и добавляем для активного элемента
            $headers.each((index, el) => {
                if ((window.scrollY + 100) >= $(el).offset().top) { //innerHeight / 4
                    $(navItem).removeClass(activeClass);
                    const selector = 'a[href="#' + el.id + '"]';
                    $(selector).first().addClass(activeClass);
                }
            });
        });
    }
}