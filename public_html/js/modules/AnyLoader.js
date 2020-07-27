import ImageManipulation from "/js/modules/ImageManipulation.js";
//import InterfaceLib from "/js/modules/InterfaceLib.js";
import Interface from "/js/modules/InterfaceLib.js";
/**
 * Класс загрузки файлов с проверками, выводом состояний и предпросмотром
 * Параметры params = {
 *      info - селектор куда выводить состояние
 *      spinner - куда выводить спинер
 *      file - объект file
 *      preView - селектор области предпросмотра
 *      fileName - имя файла на сервере
 *      fileSizeLimit - лимит на загрузку, если есть
 *      imgSize - объект с размерами {[width], [height], [maxSide]}
 *      quality - качество (0-1)
 *      API - адрес сервиса на сервере
 *      serverVar - имя переменной на сервере
 *      tasks - массив с последовательностью перечисления вызываемых методов, например:
 *          tasks: ['spinnerShow', 'prepareImage', 'fileUpload', 'preViewImage', 'spinnerHide']
 *  }
 * Методы:
 *      spinnerShow(), spinnerHide(),
 *      checkFileSize(maxSize = 100000) проверяет размер и выводит предупреждение
 *      prepareImage() - если изображение превышает размер fileSizeLimit, то сжимает его до размеров 
 *          imgSize: {[width], [height], [maxSide]} и качества quality (0-1);
 *      preViewImage() - загружает предпросмотр по селектору preView
 *      fileUpload() - загружает файл на сервер по адресу API c именем переменной loadedVar
 */
export default class AnyLoader {

    // параметры: селектор с секциями, селектор элементов меню, класс изменения выделения
    constructor(params) {
        // инициируем состояние
        this.params = params;
        // в случае проверок останавливает цепочку методов
        this.status = true;

        // очищаем статус
        $(params.info).text('');

        // подключаем библиотеку интерфейсов
        //this.Interface = new InterfaceLib();
    }

    // активируем спиннер
    spinnerShow() {
        // проверяем статус цепочки
        if (!this.status) return this.status;

        $(this.params.spinner).removeClass('d-none');
    }

    // скрываем спиннер
    spinnerHide() {
        $(this.params.spinner).addClass('d-none');
    }

    // проверяет размер файла
    checkFileSize() {
        // проверяем статус цепочки
        if (!this.status) return this.status;

        // проверяем размер
        if (this.params.file.size > this.params.fileSizeLimit) {
            Interface.showInfo(this.params.info, 'Слишком большой размер', 'badge-danger', 5000);
            this.status = false;
        }
    }

    /** Проверяет файл на размер и если требуется сжимает до указанных размеров и качества с автоориентацией */
    prepareImage() {
        // проверяем статус цепочки
        if (!this.status) return this.status;
        // проверяем надо ли сжимать
        if (this.params.file.size <= this.params.fileSizeLimit) return this.status;
        if (this.params.file.name.match(/.+\.pdf$/i)) {
            this.checkFileSize();
            return this.status;
        }

        const thisObj = this;
        return new Promise(resolve => {
            // формируем новое изображение
            new ImageManipulation()
                .compressImagePr(
                    {
                        file: this.params.file,
                        name: this.params.fileName
                    },
                    this.params.imgSize, this.params.quality)
                .then(newFile => {
                    // перезаписываем file - новым изображением
                    thisObj.params.file = newFile;

                    resolve(this.status);
                });
        });
    }

    // клонирует секцию показа картинки
    cloneImageSection() {
        // проверяем статус цепочки
        if (!this.status) return this.status;

        $(this.params.cloneSectionWhat).clone(true).appendTo(this.params.cloneSectionWhere).removeClass('d-none');
    }

    // загружает и выводит картинку в объект img
    preViewImage() {
        // проверяем статус цепочки
        if (!this.status) return this.status;

        const thisObj = this;
        return new Promise(resolve => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = $(thisObj.params.preView).get(0);
                img.src = e.target.result;
            }
            reader.readAsDataURL(thisObj.params.file);
            // добавляем название файла в атрибут alt тега img
            $(thisObj.params.preView).get(0).alt = thisObj.params.file.name;

            resolve(thisObj.status);
        });
    }

    // загружает файл на сервер
    fileUpload() {
        // проверяем статус цепочки
        if (!this.status) return this.status;

        return new Promise((resolve, reject) => {
            // формируем объект загрузки
            const data = new FormData();

            data.append(this.params.serverVar, this.params.file, this.params.fileName);

            // загружаем на сервер
            const thisObj = this;
            $.ajax(this.params.API, {
                method: 'POST',
                data: data,
                dataType: 'json',
                cache: false,
                processData: false,
                contentType: false,
                success: function uploadSuccess(response) {
                    if (response.code === 0) {
                        Interface.showInfo(thisObj.params.info, 'Что-то пошло не так, попробуйте позже', 'badge-danger', 5000);
                        console.log(response.message);
                        thisObj.status = false;
                    }
                    if (response.code === 2) {
                        Interface.showInfo(thisObj.params.info, 'Такой файл уже загружен', 'badge-danger', 5000);
                        thisObj.status = false;
                    }
                    resolve(thisObj.status);
                },
                error: function uploadError(xhr, error) {

                    console.log('Sending error: ' + error);

                    Interface.showInfo(thisObj.params.info, 'Что-то пошло не так, попробуйте позже', 'badge-danger', 5000);
                    thisObj.status = false;
                    resolve(thisObj.status);
                }
            });
            //console.log(this.status);
        });
    }

    // инициация загрузки
    async run() {        
        // перебираем функции в переданном порядке        
        for (let task of this.params.tasks) {

            await this[task]();
        }

        return this.status;
    }

    getStatus() {
        return this.status;
    }
}