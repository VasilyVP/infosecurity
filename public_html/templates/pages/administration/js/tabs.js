// логика закладок
// обрабатываем события перелистывания вкладок

// вкладка Рассылок
let first_mailing = true;
$('a[data-toggle="tab"').on('shown.bs.tab', function (event) {
    // проверяем первое открытие закладки
    if (event.target.id == 'nav-mailing-tab') {
        if (first_mailing) {
            // инициируем заполнение select-ов шаблонами, списками и tags, а также список истории
            $('#mailing_template, #mailing_list, #tags, div[data-function="mailings-history"]:first').trigger('new');

            first_mailing = false;
        }
    }
});