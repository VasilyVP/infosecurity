// обработка формы регистрации нового пользователя
import { PATHS, KEYS, USE_SESSION_TOKEN } from '/js/config.js';
import Captcha from '/js/modules/reCAPTCHAhandler.js';
import InterfaceLib from '/js/modules/InterfaceLib.js';

// очищаем галочку Ознакомлены с Условиями при каждой загрузке
document.getElementById('conditions_check').checked = false;

// проверяем качество пароля
const $passwd1 = $('#user_password');
const $ok1 = $('#password1_ok');

$passwd1.on('blur', function () {
    const passw = $(this).val();

    if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{6,20})/.test(passw)) {
        $ok1.text('Слабый пароль')
            .addClass('badge-danger')
            .removeClass('badge-success');
        // сохраняем статус в поле
        $passwd1.data('passwOk', false);
    } else {
        $ok1.text('Ok')
            .removeClass('badge-danger')
            .addClass('badge-success');
        // сохраняем статус в поле
        $passwd1.data('passwOk', true);
    }
});

//проверяем совпадение паролей
const $passwd2 = $('#user_password2');
const $ok2 = $('#password2_ok');

$passwd2.on('blur', function () {
    if ($passwd1.val() != $passwd2.val()) {
        $ok2.text('Пароль не совпадает!')
            .addClass('badge-danger')
            .removeClass('badge-success');
        // сохраняем статус в поле
        $passwd2.data('passwSame', false);
    } else {
        $ok2.text('Пароль совпадает')
            .removeClass('badge-danger')
            .addClass('badge-success');
        // сохраняем статус в поле
        $passwd2.data('passwSame', true);
    }
});

// при закрытии окна регистрации очищаем уведомления
$('#close_registration_btn').click(e => {
    $ok1.add($ok2).text('');
    $('#user_password, #user_password2').val('');
});

// обрабатываем чекбокс Согласия с Положениями и условиями
$('#conditions_check').change(function () {
    $('#register_btn').toggleClass('sx-btn-off').toggleClass('sx-btn-on').prop('disabled', (i, val) => !val);
});

// обрабатываем кнопку Зарегистрировать
const $registrationForm = $('#user_registration_form');

$registrationForm.on('submit', function (event) {
    event.preventDefault();

    const $note = $('#notification');
    $note.text('');

    /*
    // запускаем события и проверку паролей
    $passwd1.trigger('blur');
    if (!$passwd1.data('passwOk')) return;
    $passwd2.trigger('blur');
    if (!$passwd2.data('passwSame')) return;
    */

    // визуализируем спинер
    $('#spinner').toggleClass('d-none');

    // инициируем reCaptcha и по получении токена - делаем запрос
    new Captcha('registration').responsePromise().then(function (token) {
        $('#captcha_token').val(token);

        // собираем и сериализуем все поля формы
        const serialFields = $registrationForm.serialize();
        // отправляем запрос и обрабатываем ответ
        $.post(PATHS.userRegistrationAPIurl, serialFields, function (response) {            
            // отключаем спинер
            $('#spinner').toggleClass('d-none');

            if (response.code === 1) {
                //$note.text('Вам на e-mail направлено письмо. Для завершения регистрации перейдите по ссылке в нём.');
                InterfaceLib.showStatusCorrect('#confirm_section', 'yes');
                // деактивируем поля
                $('input[id*="user_"]', '#user_registration_form').prop('readonly', (i, val) => {
                    return !val;
                });
                // скрываем кнопку регистрации
                $('#register_btn').toggleClass('d-none');
                // показываем кнопку Закрыть
                $('#register_close').toggleClass('d-none');
            } else {
                InterfaceLib.showStatusCorrect('#confirm_section', 'no');
                $note.removeClass('d-none');
                if (response.code === 1062) $note.text('Такой e-mail уже зарегистрирован');
                if (response.code === 0) $note.text('Что-то пошло не так, попробуйте пожалуйста позже');
                if (response.code === 2) $note.text('Странно, что вы это видите, но вы определены как робот :(. Обратитесь в поддержку.');
            }
        }, 'json');
    });
});
