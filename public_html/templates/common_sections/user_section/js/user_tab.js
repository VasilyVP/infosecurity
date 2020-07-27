import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';
// элементы с подсказками статусов полей
const $note = $('#update_ok');
const $ok = $('#password_ok');

// переключаем доступность редактирования на владке Пользователь
$('#edit-toggle').change(function () {
    // активируем поля
    $('input[id*="user_"]', '#user_form').prop('readonly', (i, val) => {
        return !val;
    });
    // активируем кнопку
    const $userSubmit = $('#user_submit');
    if (this.checked) $userSubmit.removeClass('sx-btn-off').addClass('sx-btn-on');
    else $userSubmit.addClass('sx-btn-off').removeClass('sx-btn-on');
    $userSubmit.prop('disabled', (i, atr) => { return !atr });

    // активируем checkbox для нового пароля
    $('#edit-password').prop('disabled', (i, val) => { return !val });
    // очищаем поля подсказок
    if (this.checked) {
        $note.text('');
        $ok.text('');
    }
});
// переключаем доступность полей для нового пароля
$('#edit-password').change(function () {
    $('input[id^="password"]', '#user_form').prop('readonly', (i, val) => { return !val; });
});

// проверяем качество пароля
const $passwd1 = $('#password1');

$passwd1.on('keyup', function () {
    const passw = $(this).val();

    if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{6,20})/.test(passw)) {
        $ok.text('Слабый пароль')
            .addClass('badge-danger')
            .removeClass('badge-success');
        // сохраняем статус в поле
        $passwd1.data('passwOk', false);
    } else {
        $ok.text('Ok')
            .removeClass('badge-danger')
            .addClass('badge-success');
        // сохраняем статус в поле
        $passwd1.data('passwOk', true);
    }
});

//проверяем совпадение паролей
const $passwd2 = $('#password2');

$passwd2.on('keyup', function () {
    const passw = $(this).val();

    if ($passwd1.val() != passw) {
        $ok.text('Пароль не совпадает!')
            .addClass('badge-danger')
            .removeClass('badge-success');
        $passwd2.data('passwSame', false);
    } else {
        $ok.text('Ok')
            .removeClass('badge-danger')
            .addClass('badge-success');
        $passwd2.data('passwSame', true);
    }
});

const $userForm = $('#user_form');

// проверяем Ok на пароли и отправляем форму
$userForm.on('submit', function (event) {
    event.preventDefault();
    // если вводились новые пароли
    if ($('#edit-password').prop('checked')) {
        // запускаем события проверки паролей
        $passwd1.trigger('keyup');
        if (!$passwd1.data('passwOk')) return;
        $passwd2.trigger('keyup');
        if (!$passwd2.data('passwSame')) return;
    }

    // активируем спинер
    $('#user_edit_spinner').removeClass('d-none');

    // собираем и сериализуем все поля формы
    const serialFields = $userForm.serialize();

    // отправляем запрос и обрабатываем ответ
    $.post(PATHS.userUpdateAPIurl, serialFields, function (response) {

        // если данные обновились
        if (response.code === 1) {
            $note.text('Данные обновлены')
                .removeClass('badge-danger')
                .addClass('badge-success');

            // деактивируем поля новых паролей
            $('input[id^="password"]', '#user_form').prop('readonly', true);
            // checkbox нового пароля снимаем и блокируем
            $('#edit-password').prop('checked', false);
            // все поля блокируем
            $('#edit-toggle').prop('checked', false);
            $('#edit-toggle').trigger('change');
        }
        // если пароль не верен
        if (response.code === 2) {
            $note.text('Пароль не верен')
                .removeClass('badge-success')
                .addClass('badge-danger');
        }
        // если дублирующий email
        if (response.code === 1062) {
            $note.text('Такой e-mail уже зарегистрирован')
                .removeClass('badge-success')
                .addClass('badge-danger');
        }
        // если нет обновленных данных
        if (response.code === 300) {
            $note.text('Данные не менялись')
                .removeClass('badge-danger')
                .addClass('badge-success');
        }
        // прочие ошибки
        if (response.code === 0) {
            $note.text('Что-то пошло не так, попробуйте пожалуйста позже')
                .removeClass('badge-success')
                .addClass('badge-danger');
        }
        // убираем спинер
        $('#user_edit_spinner').addClass('d-none');
        // удаляем уведомления через N секунд
        setTimeout(function () {
            $note.add($ok).removeClass('badge-success').removeClass('badge-danger').text('');
            $('#password1, #password2, #user_confirm_password').val('');
        }, 5000);
    }, 'json');
});