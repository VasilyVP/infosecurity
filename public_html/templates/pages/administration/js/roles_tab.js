// описывает логику закладки Роли раздела Администрирования
import { PATHS, KEYS, USE_SESSION_TOKEN } from '/js/config.js';
// подключаем автодополнение пользователей
import UserComplite from '/js/modules/UserComplite.js';

const users = new UserComplite({
    completionID: 'search_field'
});

// запоминаем первый блок UserInfo
const $firstUserInfo = $('div[data-function="userInfo"]:first');

// убираем выделение кнопок при ручном вводе и очищаем список
$('#search_field').click(function () {
    $('a[data-toggle="pill"]').removeClass('active');
    $firstUserInfo.nextAll().remove();
});

// очищаем статус при изменении select
$('select', 'div[data-function="userInfo"]').change(function () {
    $('i[data-function="showUpdate"]').addClass('d-none');
});

// выводит список пользователей
function showUsers(data) {
    // проверяем data
    if (!data) return;

    for (let user of data.users) {
        // клонируем элемент userInfo и делаем его видимым
        const $lastUserInfo = $firstUserInfo.clone(true).appendTo('#users_role_form'); // div[data-function="user_group"]:last
        $lastUserInfo.removeClass('d-none');

        // запоминаем поле email
        const $email = $('input[data-function="email"]', $lastUserInfo);

        // заполняем имя и email
        $('input[data-function="fullName"]', $lastUserInfo).val(user.name + ' ' + user.surname);
        $email.val(user.email);

        // заполняем options в role select
        const $roleSelect = $('select[data-function="role"]', $lastUserInfo);
        data.rolesList.forEach(role => {
            const $option = $roleSelect.children(':last').clone();
            $option.appendTo($roleSelect).text(role.name).val(role.id_role);
            // выделяем активную роль
            if (user.role == role.name) {
                $option.prop('selected', true);
            }
        });
        //заполняем статус в active select
        const $statusSelect = $('select[data-function="active"]', $lastUserInfo);
        $statusSelect.children().each((i, el) => {
            if (user.active == $(el).val()) {
                $(el).prop('selected', true);
            }
        });

        const $aLinkToPage = $('a[name="link-to-pa-page"]', $lastUserInfo);
        // добавляем обработчик перехода по ссылке в ЛК пользователя
        if ($roleSelect.val() == 3) {
            // записываем ссылку в email
            $aLinkToPage.prop('href', location.origin + '/personal_area');
            $email.addClass('text-primary').css('cursor', 'pointer');
            // пишем куки с user email при клике по ссылке
            $aLinkToPage.click((e) => {
                document.cookie = `userLogin=${$email.val()};max-age=3600;`;
            });
        } else {
            $aLinkToPage.click(() => {return false});
        }
    }
}

// выводим админов
$('#admins_pill').click(function () {
    // очищаем список пользователей
    $firstUserInfo.nextAll().remove();
    $.getJSON(PATHS.getUsersListAPIurl, 'role=admin', response => {
        // выводим пользователей
        if (response.code === 1) showUsers(response.data);
    });
});

// выводим модераторов
$('#moderators_pill').click(function () {
    // очищаем список пользователей
    $firstUserInfo.nextAll().remove();
    $.getJSON(PATHS.getUsersListAPIurl, 'role=moderator', response => {
        if (response.code === 1) showUsers(response.data);
    });
});

// выводим пользователей по search
$('#search_field').on('suggestion', function (event, inputData) {
    const data = {
        'rolesList': users.rolesList,
        'users': [inputData]
    }
    showUsers(data);
});

// отправляем данные по нажатию Сохранить
$('button[data-function="save"]').click(function (event) {
    const $userForm = $(this).closest('div[data-function="userInfo"]');
    const data = $(':input', $userForm).serialize();

    const thisObj = this;
    $.post(PATHS.setUserRoleStatusAPIurl, data, response => {
        const $ok = $(thisObj).nextAll('i:first');
        const $no = $(thisObj).nextAll('i:last');

        // выводим уведомление о результате
        if (response.code === 1) {
            $no.addClass('d-none');
            $ok.removeClass('d-none');
            setTimeout(() => { $ok.addClass('d-none') }, 5000);
        } else {
            console.log(response);
            $no.removeClass('d-none');
            $ok.addClass('d-none');
            setTimeout(() => { $no.addClass('d-none') }, 5000);
        }
    }, 'json');
});