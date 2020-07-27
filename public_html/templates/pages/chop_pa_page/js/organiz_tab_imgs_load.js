// Управление загрузкой и отображением изображений и других файлов
import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';
import AnyLoader from "/js/modules/AnyLoader.js";
import RuToEnTranslit from "/js/modules/RuToEnTranslit.js";
import Interface from "/js/modules/InterfaceLib.js";

export default class OrganizationTabImgsLoad {
    constructor() {
        // показываем и загружаем логотип ЧОП
        $('#logo_file').change(function () {
            new AnyLoader(
                {
                    spinner: '#logo_spinner',
                    info: '#logo_ok',
                    preView: '.chop_logo',
                    file: this.files[0],
                    fileName: 'logo',
                    fileSizeLimit: 100000,
                    imgSize: { width: 200 },
                    quality: 1,
                    API: PATHS.organizationFilesLoadAPIurl,
                    serverVar: 'logo',
                    tasks: ['spinnerShow', 'prepareImage', 'fileUpload', 'preViewImage', 'spinnerHide'] // 'checkFileSize', 
                })
                .run().then(status => {
                    if (status) {
                        // добавляем признак наличия фото
                        $('#logo_flag').val('true');
                        $('#chop_form').trigger('submit');
                    }
                });
        });

        // показываем и загружаем фотографии ЧОП
        $('#add_chop_photo').change(function (e) {
            // проверяем количество фотографий
            const $collection = $('#chop-photo-collection');
            let count = $collection.data('count');
            if (count == 3) {
                Interface.showInfo('#photo_ok', 'Не более ' + count + ' фотографий', 'badge-info', 5000);
                return;
            }

            // проверяем тип файла
            if (!this.files[0].name.match(/.+(\.(jpe?g))$/i)) {
                Interface.showInfo('#photo_ok', 'Можно загрузить только JPG/JPEG формат', 'badge-danger', 5000);
                return;
            }

            // формируем имя файла с транслитерацией русского на английский
            const fileName = new RuToEnTranslit().translitFileNameRuToEn(this.files[0].name);

            new AnyLoader(
                {
                    spinner: '#photo_spinner',
                    info: '#photo_ok',
                    preView: 'img[name="chop-photo-img"]:last',
                    cloneSectionWhat: 'div[name="chop-photo-element"]:first',
                    cloneSectionWhere: '#chop-photo-collection',
                    file: this.files[0],
                    fileName: fileName,
                    fileSizeLimit: 350000,
                    imgSize: { maxSide: 500 },
                    quality: 0.9,
                    API: PATHS.organizationFilesLoadAPIurl,
                    serverVar: 'photo',
                    tasks: ['spinnerShow', 'prepareImage', 'fileUpload', 'cloneImageSection', 'preViewImage']
                })
                .run().then(status => {
                    // увеличиваем счетчик
                    if (status) {
                        $collection.data('count', ++count);
                        // добавляем название файла в скрытое поле
                        $('input[name="chop_photo_file_name[]"]:last').val(fileName);

                        $('#chop_form').trigger('submit');
                    }
                    $('#photo_spinner').addClass('d-none');
                });
        });

        // обработчик удаления фотографии
        $('button[name="delete-img"]').click(function (e) {
            const $collection = $('#chop-photo-collection');
            let count = $collection.data('count');

            // запоминаем имя файла
            const $chopPhotoEl = $(this).parents('div[name="chop-photo-element"]');
            const fileName = $('input[name="chop_photo_file_name[]"]', $chopPhotoEl).val();
            // удаляем файл на сервере
            $.post(PATHS.organizationFilesLoadAPIurl, { deleteFile: fileName }, function (response) {
                // удаляем секцию и уменьшаем счетчик
                if (response.code === 1) {
                    $chopPhotoEl.remove();
                    $collection.data('count', --count);
                    $('#chop_form').trigger('submit');
                }
            }, 'json');
        });

        // показываем и загружаем лицензии
        $('#add_chop_licence').change(function (e) {
            // проверяем количество фотографий
            const $collection = $('#chop_licence_collection');
            let count = $collection.data('count');
            if (count == 3) {
                Interface.showInfo('#licence_ok', 'Не более ' + count + ' изображений', 'badge-info', 5000);
                return;
            }

            // проверяем тип файла
            if (!this.files[0].name.match(/.+(\.(jpe?g))$/i)) { //|pdf
                Interface.showInfo('#licence_ok', 'Можно загрузить только JPG/JPEG формат', 'badge-danger', 5000);
                return;
            }
            // формируем имя файла с транслитерацией русского на английский
            const fileName = new RuToEnTranslit().translitFileNameRuToEn(this.files[0].name);

            new AnyLoader(
                {
                    spinner: '#licence_spinner',
                    info: '#licence_ok',
                    preView: 'img[name="chop-licence-img"]:last',
                    cloneSectionWhat: 'div[name="chop-licence-element"]:first',
                    cloneSectionWhere: '#chop_licence_collection',
                    file: this.files[0],
                    fileName: fileName,
                    fileSizeLimit: 350000,
                    imgSize: { maxSide: 500 },
                    quality: 0.9,
                    API: PATHS.organizationFilesLoadAPIurl,
                    serverVar: 'licence',
                    tasks: ['spinnerShow', 'prepareImage', 'fileUpload', 'cloneImageSection', 'preViewImage']
                })
                .run().then(status => {
                    // увеличиваем счетчик
                    if (status) {
                        $collection.data('count', ++count);
                        // добавляем название файла в скрытое поле
                        $('input[name="chop_licence_file_name[]"]:last').val(fileName);
                        $('#chop_form').trigger('submit');
                    }
                    $('#licence_spinner').addClass('d-none');
                });
        });

        // обработчик удаления лицензии
        $('button[name="delete-licence"]').click(function (e) {
            const $collection = $('#chop_licence_collection');
            let count = $collection.data('count');

            // запоминаем имя файла
            const $chopLicenceEl = $(this).parents('div[name="chop-licence-element"]');
            const fileName = $('input[name="chop_licence_file_name[]"]', $chopLicenceEl).val();
            // удаляем файл на сервере
            $.post(PATHS.organizationFilesLoadAPIurl, { deleteFile: fileName }, function (response) {
                // удаляем секцию и уменьшаем счетчик
                if (response.code === 1) {
                    $chopLicenceEl.remove();
                    $collection.data('count', --count);
                    $('#chop_form').trigger('submit');
                }
            }, 'json');
        });

        // показываем и загружаем отзывы
        $('#add_chop_feedback').change(function (e) {
            // проверяем количество фотографий
            const $collection = $('#chop_feedback_collection');
            let count = $collection.data('count');
            if (count == 3) {
                Interface.showInfo('#feedback_ok', 'Не более ' + count + ' документов', 'badge-info', 5000);
                return;
            }

            // проверяем тип файла
            if (!this.files[0].name.match(/.+(\.(pdf))$/i)) {
                Interface.showInfo('#feedback_ok', 'Можно загрузить только PDF формат', 'badge-danger', 5000);
                return;
            }
            // формируем имя файла с транслитерацией русского на английский
            const fileName = new RuToEnTranslit().translitFileNameRuToEn(this.files[0].name);

            new AnyLoader(
                {
                    spinner: '#feedback_spinner',
                    info: '#feedback_ok',
                    //preView: 'img[name="chop-licence-img"]:last',
                    cloneSectionWhat: 'div[name="chop-feedback-element"]:first',
                    cloneSectionWhere: '#chop_feedback_collection',
                    file: this.files[0],
                    fileName: fileName,
                    fileSizeLimit: 350000,
                    API: PATHS.organizationFilesLoadAPIurl,
                    serverVar: 'feedback',
                    tasks: ['checkFileSize', 'spinnerShow', 'fileUpload', 'cloneImageSection']// , 'spinnerHide'
                })
                .run().then(status => {
                    // увеличиваем счетчик
                    if (status) {
                        $collection.data('count', ++count);
                        // заполняем название файла для пользователя и на сервере
                        $('input[name="feedback_file_name[]"]:last').val(fileName);
                        $('input[name="feedback_name[]"]:last').val(this.files[0].name);
                        $('#chop_form').trigger('submit');
                    }
                    $('#feedback_spinner').addClass('d-none');
                });
        });

        // обработчик удаления отзыва
        $('button[name="delete-feedback"]').click(function (e) {
            const $collection = $('#chop_feedback_collection');
            let count = $collection.data('count');

            // запоминаем имя файла
            const $chopFeedbackEl = $(this).parents('div[name="chop-feedback-element"]');
            const fileName = $('input[name="feedback_file_name[]"]', $chopFeedbackEl).val();
            // удаляем файл на сервере
            $.post(PATHS.organizationFilesLoadAPIurl, { deleteFile: fileName }, function (response) {
                // удаляем секцию и уменьшаем счетчик
                if (response.code === 1) {
                    $chopFeedbackEl.remove();
                    $collection.data('count', --count);
                    $('#chop_form').trigger('submit');
                }
            }, 'json');
        });
    }

}