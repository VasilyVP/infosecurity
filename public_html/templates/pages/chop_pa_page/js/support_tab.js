import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';
import Captcha from '/js/modules/reCAPTCHAhandler.js';

export default class SupportTab {
    constructor() {
        // описывает логику закладки Поддержка
        const $sendOk = $('#send_ok');

        // заполняем поле загружаемых файлов
        const $supportFiles = $('#support_files');
        $supportFiles.data('filesOk', true);
        // обрабатываем выбор файлов и заполнение поля файлов
        $supportFiles.change(function () {
            // обнуляем filesOk
            $supportFiles.data('filesOk', true);
            // формируем список файлов
            let filesName = [];
            // проверяем количество файлов
            if (this.files.length > 5) {
                $(this).data('filesOk', false);
                $sendOk
                    .text('Много файлов')
                    .removeClass('badge-success')
                    .addClass('badge-danger');
            }
            // заполняем список и считаем объем файлов
            let size = 0;
            for (let file of this.files) {
                filesName.push(file.name);
                size += file.size;
            }
            // проверяем объем файлов
            if (size > 8300000) {
                $(this).data('filesOk', false);
                $sendOk
                    .text('Вложений больше 8 Мб')
                    .removeClass('badge-success')
                    .addClass('badge-danger');
            }
            // заполняем или очищаем поле вложений и статус    
            filesName = filesName.join(', ');
            $('#files_label').text(filesName);

            if ($(this).data('filesOk')) $sendOk.text('');
        });

        // обрабатываем отправку формы
        const $supportForm = $('#support_form');
        $supportForm.submit(function (event) {
            event.preventDefault();
            // проверяем объем/число файлов
            if (!$supportFiles.data('filesOk')) return;

            // визуализируем спинер
            $('#spiner').toggleClass('d-none');

            // инициируем получение reCAPTCHA и после получения формируем запрос
            new Captcha('support', KEYS).responsePromise().then(function (token) {
                $('#captcha_support_token').val(token);

                // формируем запрос
                // создаем объект данных
                const data = new FormData();
                // добавляем имя пользователя
                data.append('name', $('#user_name').val());
                data.append('surname', $('#user_surname').val());
                data.append('patronymic', $('#user_patronymic').val());
                // добавляем поле тема и сообщение
                data.append('subject', $('#support_subject').val());
                data.append('message', $('#support_message').val());
                data.append('captcha_support_token', $('#captcha_support_token').val());
                // добавляем вложения
                const files = $supportFiles.get(0).files;
                for (let i = 0, c = files.length; i < c; i++) {
                    data.append(i, files[i], files[i].name);
                }

                $.ajax(PATHS.supportSendAPIurl, {
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
                            $('#support_submit_button').toggleClass('d-none');
                            $('#support_reset_button').toggleClass('d-none');
                        } else {
                            $sendOk
                                .text('Что-то пошло не так, попробуйте позже')
                                .removeClass('badge-success')
                                .addClass('badge-danger');
                            console.log(response.message);
                        }
                        // убираем спинер
                        $('#spiner').toggleClass('d-none');
                    },
                    error: function (xhr, error) {
                        console.log('Sending error: ' + error);
                        $sendOk
                            .text('Что-то пошло не так, попробуйте позже')
                            .removeClass('badge-success')
                            .addClass('badge-danger');
                        // убираем спинер
                        $('#spiner').toggleClass('d-none');
                    }
                });
            });
        });

        // при нажатии Очистить - меняем кнопки обратно и востанавливаем поля
        $('#support_reset_button').click(function () {
            $('#support_submit_button').toggleClass('d-none');
            $('#support_reset_button').toggleClass('d-none');
            $sendOk.text('');
            $('#files_label').text('Приложить документы');
        });
    }

}