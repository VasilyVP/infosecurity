// обработка формы логина пользователя
import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';

const $loginForm = $('#user_login_form');
const $loginEmail = $('#login_email');
const $note = $('#login_notification');

// обрабатываем событие submit формы
$loginForm.on('submit', function (event) {
    event.preventDefault();

    // собираем и сериализуем значения формы
    const serialFields = $loginForm.serialize();

    // проверяем логин-пароль
    $.post(PATHS.userLoginAPIurl, serialFields, function (response) {

        const $note = $('#login_notification');
        // если логин-пароль совпадает - переадресуем на личный кабинет
        if (response.code === 1) location.assign('/' + response.rout);
        // иначе выводим предупреждение
        else if (response.code === 2) $note.text('Пользователь не зарегистрирован');
        else if (response.code === 3) $note.text('Пароль неверен или e-mail не подтвержден');
        else if (response.code === 0) $note.text('Что-то пошло не так, попробуйте позже');        
    }, 'json');
});

// убираем подсказку
$loginEmail.focusout(function() {
    $(this).popover('hide');
});

// обрабатываем клик по "забыл пароль"
//const $forgetPassw = $('#forget_password');
$('#forget_password').click(function(){

    const login = $loginEmail.val();
    // тестируем email на валидность
    if (!/[a-z0-9_.-]+@[a-z0-9.-]+\.[a-z]{2,6}/.test(login)) {
        $loginEmail.popover('show');
        return;
    }
    // отправляем запрос на email и если Ок - уведомляем
    const serialFields = $loginForm.serialize();
    $.post(PATHS.userPasswResetAPIurl, serialFields, function (response) {
    
        if (response.code === 1) {
            $('#login_group').addClass('d-none');
            $('#recovery_email').text(login);
            $('#pass_recovery_group').removeClass('d-none');
        }
        if (response.code === 2) $note.text('E-mail не зарегистрирован');
        if (response.code === 0) $note.text('Что-то пошло не так, попробуйте пожалуйста позже');
    }, 'json');    
});

// обработка клика на кнопку Ok инфо-окна восстановления пароля - отображает опять окно логина
$('#close_btn').click(e => {
    $('#login_group').removeClass('d-none');
    $('#pass_recovery_group').addClass('d-none');
});