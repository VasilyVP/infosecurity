// обработка формы сброса пароля
import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';

// проверяем качество пароля
const $passwd1 = $('#user_password');
const $ok1 = $('#password1_ok');

$passwd1.on('keyup', function () {
    const passw = $(this).val();
    // проверяем пароль
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

$passwd2.on('keyup', function () {
    
    if ($passwd1.val() != $passwd2.val()) {
        $ok2.text('Пароль не совпадает!')
            .addClass('badge-danger')
            .removeClass('badge-success');
        // сохраняем статус в поле
        $passwd2.data('passwSame', false);
    } else {
        $ok2.text('Ok')
            .removeClass('badge-danger')
            .addClass('badge-success');
        // сохраняем статус в поле
        $passwd2.data('passwSame', true);
    }
});

// обрабатываем кнопку Зарегистрировать
const $resetPasswForm = $('#reset_password_form');

$resetPasswForm.on('submit', function (event) {
    event.preventDefault();
    // запускаем события и проверку паролей
    $passwd1.trigger('keyup');
    if (!$passwd1.data('passwOk')) return;
    $passwd2.trigger('keyup');
    if (!$passwd2.data('passwSame')) return;

    // собираем и сериализуем все поля формы
    const serialFields = $resetPasswForm.serialize();
    // отправляем запрос и обрабатываем ответ
    $.post(PATHS.userNewPasswAPIurl, serialFields, function (response) {
        const $note = $('#notification');
        if (response.code === 1) {
            $note.text('Ваш пароль обновлен');
            // деактивируем поля
            $('input[id*="user_"]', '#reset_password_form').prop('readonly', 'true');
            // блокируем кнопку
            $('#save_passw').prop('disabled', 'true');
        }
        if (response.code === 0) $note.text('Что-то пошло не так, попробуйте пожалуйста позже');
        if (response.code === 2) $note.text('Странно, но ваш email похоже не зарегистрирован');
    },
        'json');
});
