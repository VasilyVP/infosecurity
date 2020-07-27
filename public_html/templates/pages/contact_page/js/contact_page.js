import { PATHS, KEYS, USE_SESSION_TOKEN } from '/js/config.js';
import Captcha from '/js/modules/reCAPTCHAhandler.js';

// описывает логику закладки Поддержка
const $sendOk = $('#contact_send_ok');

// обрабатываем отправку формы
const $contactForm = $('#contact_form');
$contactForm.submit(function (event) {
    event.preventDefault();

    // визуализируем спинер
    $('#contact_spiner').toggleClass('d-none');

    // инициируем получение reCAPTCHA и после получения формируем запрос
    new Captcha('contact', KEYS).responsePromise().then(function (token) {
        $('#captcha_contact_token').val(token);

        // формируем запрос
        // создаем объект данных
        const data = new FormData();
        // добавляем поля
        data.append('email', $('#contact_email').val());
        data.append('reason', $('#contact_reason').val());
        data.append('subject', $('#contact_subject').val());
        data.append('message', $('#contact_message').val());
        data.append('captcha_contact_token', $('#captcha_contact_token').val());

        $.ajax(PATHS.contactSendAPIurl, {
            method: 'POST',
            data: data,
            dataType: 'json',
            cache: false,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.code == 1) {
                    $sendOk
                        .text('Сообщение отправлено')
                        .addClass('badge-success')
                        .removeClass('badge-danger');
                    // меняем кнопки
                    $('#contact_submit_button').toggleClass('d-none');
                    $('#contact_reset_button').toggleClass('d-none');
                } else if (response.code == 2) {
                    $sendOk
                        .text('Возможно, указан некорректный e-mail')
                        .removeClass('badge-success')
                        .addClass('badge-danger');
                    console.log(response.message);
                } else {
                    $sendOk
                        .text('Что-то пошло не так, попробуйте позже')
                        .removeClass('badge-success')
                        .addClass('badge-danger');
                    console.log(response.message);
                }
                // убираем спинер
                $('#contact_spiner').toggleClass('d-none');
            },
            error: function (xhr, error) {
                console.log('Sending error: ' + error);
                $sendOk
                    .text('Что-то пошло не так, попробуйте позже')
                    .removeClass('badge-success')
                    .addClass('badge-danger');
                // убираем спинер
                $('#contact_spiner').toggleClass('d-none');
            }
        });
    });
});

// при нажатии Очистить - меняем кнопки обратно и востанавливаем поля
$('#contact_reset_button').click(function () {
    $('#contact_submit_button').toggleClass('d-none');
    $('#contact_reset_button').toggleClass('d-none');
    $sendOk.text('');
});