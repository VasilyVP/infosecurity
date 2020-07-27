/** Библиотека интерфейсных функций */
export default class InterfaceLib {
    
    /** Показывает информационное сообщение text в теге с селектором where и классом type (тип BS badge например)
     * на timeout мс.
    */
    static showInfo(where, text, type, timeout) {
        const $where = $(where);
        $where.addClass(type).text(text);
        if (timeout !== 0) setTimeout(() => { $where.removeClass(type).text(''); }, timeout);
    }

    /** Показывает Галочку подтверждения или Крестик неудачи (status = yes OR no) в элементе с селектором where и таймаутом timeout 
     * !!! ОШИБКА В АЛГОРИТМЕ!!! Ниже скорректированная
    */
    static showStatus(where, status, timeout = 5000) {        
        const $whereYes = $(where).filter('.fa-check');
        const $whereNo = $(where).filter('.fa-times');

        // приводим к первичному состоянию
        $('i.fa-check').addClass('invisible').removeClass('d-none');
        $('i.fa-times').addClass('d-none');

        if (status == 'yes') {
            $whereYes.removeClass('invisible');
            
            if (timeout !== 0) setTimeout(() => { 
                $whereYes.addClass('invisible');
            }, timeout);
        } else {
            $whereYes.addClass('d-none');
            $whereNo.removeClass('d-none');
            
            if (timeout !== 0) setTimeout(() => { 
                $whereNo.addClass('d-none');
                $whereYes.removeClass('d-none');
            }, timeout);
        }
    }

    /** Показывает Галочку подтверждения или Крестик неудачи (status = yes OR no) в элементе с селектором where и таймаутом timeout */
    static showStatusCorrect(where, status, timeout = 5000) {        
        const $whereYes = $('i.fa-check', where);
        const $whereNo = $('i.fa-times', where);

        // приводим к первичному состоянию
        $whereYes.addClass('d-none');
        $whereNo.addClass('d-none');

        if (status == 'yes') {
            $whereYes.removeClass('d-none');
            
            if (timeout !== 0) setTimeout(() => { 
                $whereYes.addClass('d-none');
            }, timeout);
        } else {
           // $whereYes.addClass('d-none');
            $whereNo.removeClass('d-none');
            
            if (timeout !== 0) setTimeout(() => { 
                $whereNo.addClass('d-none');
                //$whereYes.removeClass('d-none');
            }, timeout);
        }
    }


}