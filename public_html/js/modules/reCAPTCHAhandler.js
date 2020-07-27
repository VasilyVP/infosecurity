// класс запуска reCAPTCHA v.3
import { PATHS, KEYS, USE_SESSION_TOKEN } from '/js/config.js';

export default class Captcha {

    constructor(action, KEYS) {
        this.action = action;
        this.set = false;
        //this.KEYS = KEYS;
        //this.setTokenCookie();
    }

    // получает токен и устанавливает куку с reCAPTCHA token
    setTokenCookie() {
        const thisObj = this;
        grecaptcha.ready(function () {
            grecaptcha.execute(KEYS.reCAPTCHAsiteKey, { action: thisObj.action }).then(function (token) {
                document.cookie = 'captchaToken=' + token + ';max-age=' + 1800 + ';path=/';
                thisObj.set = true;
            });
        });

    }

    // только устанавливает куку с токеном
    justSetCookie(token = false) {
        if (!token) {
            console.log('No captcha token');
            return;
        }
        document.cookie = 'captchaToken=' + token + ';max-age=' + 1800 + ';path=/';
        this.set = true;
    }

    // выполняет запрос на получение токена (и ничего пока не делает)
    sendToServer() {
        const thisObj = this;
        grecaptcha.ready(function () {
            grecaptcha.execute(KEYS.reCAPTCHAsiteKey, { action: thisObj.action }).then(function (token) {
                /*
                $.post(PATHS.reCAPCHAverifyAPIurl, `token=${token}`, function (response) {
                    //console.log(response);
                }, 'json');
                */
            });
        });
    }

    // возвращает Promise c токеном reCAPTCHA
    responsePromise() {
        const thisObj = this;
        return new Promise((resolve, reject) => {
            grecaptcha.ready(function () {
                grecaptcha.execute(KEYS.reCAPTCHAsiteKey, { action: thisObj.action }).then(function (token) {
                    // возвращает токен
                    resolve(token);
                });
            });
        });
    }
}
