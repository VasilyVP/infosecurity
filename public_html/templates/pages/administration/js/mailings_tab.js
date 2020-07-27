// описывает логику закладки Рассылки
import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';

// загружает опции в template select
$('#mailing_template').on('new', function () {
    $(this).load(PATHS.mailingsAPIurl, 'get=templates');
});
// загружаем опции в mailing_list select
$('#mailing_list').on('new', function () {
    $(this).load(PATHS.mailingsAPIurl, 'get=mailings');
});
// загружаем опции в tags datalist options
$('#tags').on('new', function() {
    $(this).load(PATHS.mailingsAPIurl, 'get=tags');
});
// загружаем историю рассылок
$('div[data-function="mailings-history"]:first').on('new', function() {
    // очищаем историю
    $('div[data-function="mailings-history"]:not(:first)').remove();
    
    const thisObj = this;
    $.getJSON(PATHS.mailingsAPIurl, 'get=mailingsHistory', function(response) {
        for(let row of response) {
            $(thisObj).clone().insertAfter(thisObj).children().each(function(i, el) {
                $(el).text(row[i+1]);
            });
        }
    });
});

// заполняем название файла в поле загрузки шаблона и удаляем статус загрузки если был
$('#new_template').change(function () {
    $('#file_label').text(this.files[0].name);
    $('#load_ok').addClass('d-none');
    $('#load_err').addClass('d-none');
});
// заполняем название файлов в поле загрузки картинок
$('#new_imgs').change(function () {
    let names = [];
    let size = 0;
    for (let file of this.files) {
        names.push(file.name);
        size += file.size;
    }
    if (size > 5000000) names = ['Размер больше 5Мб'];
    
    $('#imgs_label').text(names.join(', '));
    $('#load_ok').addClass('d-none');
    $('#load_err').addClass('d-none');
});

// загружает новый шаблон и картинки на сервер
$('#load_template_form').submit(function (event) {
    event.preventDefault();
    // выбираем шаблон и картинки
    const file = $('#new_template').get(0).files[0];
    const imgs = $('#new_imgs').get(0).files;
    // если не выбран шаблон или картинки - выходим
    if (!(file || imgs)) return;

    // визуализируем спинер
    $('#load_spiner').toggleClass('d-none');

    // создаем объект данных
    const data = new FormData();
    // заполняем параметры
    if (file) data.append('template', file, file.name);
    if (imgs) {
        for(let i = 0, c = imgs.length; i < c; i++) data.append('image' + i, imgs[i], imgs[i].name);
    }

    $.ajax(PATHS.mailingsAPIurl, {
        method: 'POST',
        data: data,
        dataType: 'json',
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            // убираем спинер
            $('#load_spiner').toggleClass('d-none');
            // анализируем ответ
            if (response.code === 1) {
                // визуализируем Ok
                $('#load_ok').toggleClass('d-none');
                // обновляем список шаблонов
                $('#mailing_template').trigger('new');
            } else {
                // визуализируем err
                $('#load_err').toggleClass('d-none');
            }
        },
        error: function (response) {
            // визуализируем Err
            $('#load_err').toggleClass('d-none');
            // убираем спинер
            $('#load_spiner').toggleClass('d-none');
        }
    });
});

// обрабатываем "Отправить"
$('#send_mailing_form').submit(function(event) {
    event.preventDefault();
    // очищаем статусы отправки
    $('#send_ok').addClass('d-none');
    $('#send_err').addClass('d-none');
    // показываем спинер
    $('#send_spiner').toggleClass('d-none');

    const serializedFields = $(this).serialize();

    $.post(PATHS.mailingsAPIurl, serializedFields, response => {
        // убираем спинер
        $('#send_spiner').toggleClass('d-none');
        // анализируем ответ
        if (response.code === 1) {
            // визуализируем Ok
            $('#send_ok').toggleClass('d-none');
            // инициируем обновление истории рассылок
            $('div[data-function="mailings-history"]:first').trigger('new');
        } else {
            // визуализируем Err
            $('#send_err').toggleClass('d-none');
        }
    }, 'json');

});