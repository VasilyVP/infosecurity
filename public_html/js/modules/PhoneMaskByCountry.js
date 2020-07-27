/** Модуль формирования маски ввода телефона по номеру страны на основе библиотеки
 * jquery.maskedinput.min.js (подключается как плагин jQuery) 
 * в зависимости от Страны формируем маску ввода в требуемом поле Input*/

export default class PhoneMaskByCountry {

    constructor(inputFieldSelector) {
        // инициируем маску ввода
        $.mask.definitions['N'] = '[0-9]';
        $.mask.definitions['9'] = '';

        // привязываем поле ввода телефона
        this.$phone = $(inputFieldSelector);

        // описание соответствия стран и масок телефонов
        const masksMap = new Map();
        // описываем коды стран для разных длинн номеров
        const phoneCodes10 = ['+372'];
        const phoneCodes11 = ['+7'];
        const phoneCodes112 = ['+371', '+370', '+374', '+373', '+993'];
        const phoneCodes12 = ['+375', '+380', '+994', '+995', '+996', '+992', '+998'];

        // формируем соответствие длинам номеров (массивам кодов стран) и масок ввода
        masksMap.set(phoneCodes10, ' NNN-NN-NN');
        masksMap.set(phoneCodes11, ' (NNN) NNN-NN-NN');
        masksMap.set(phoneCodes112, ' (NN) NNN-NNN');
        masksMap.set(phoneCodes12, ' (NN) NNN-NN-NN');

        this.masksMap = masksMap;
    }

    // применяет маску ввода к инициированному элементу по коду страны
    applyMask(phoneCode) {
        // определяем требуемую маску
        let phoneMask;
        for (let [phoneCodes, mask] of this.masksMap) {
            if (phoneCodes.includes(phoneCode)) {
                phoneMask = mask;
                break;
            }
        }
        // применяем маску ввода
        this.$phone.mask(phoneCode + phoneMask, {autoclear: false});
    }

}