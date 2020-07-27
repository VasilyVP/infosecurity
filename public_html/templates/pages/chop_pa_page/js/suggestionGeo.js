/** обработка геопозиционирования и дополнения */
import {PATHS, KEYS, USE_SESSION_TOKEN} from '/js/config.js';
import DetermineLocation from '/js/modules/DetermineLocation.js';
import PlaceAutoCompliteG from '/js/modules/PlaceCompliteG.js';

export default class SuggestionGeo {
    constructor() {
        // инициируем геокодирование, местоположение сразу не определяем (false)
        const location = new DetermineLocation({
            coords: false,
            PATHS: PATHS,
            KEYS: KEYS
        });
        // инициируем автодополнение города
        const suggestionCity = new PlaceAutoCompliteG({
            completionID: 'city',
            countryID: 'country',
            require: 'city',
            PATHS: PATHS,
            USE_SESSION_TOKEN: USE_SESSION_TOKEN
        });
        // инициируем автодополнение адреса
        const suggestionAddr = new PlaceAutoCompliteG({
            completionID: 'address',
            countryID: 'country',
            require: 'address',
            PATHS: PATHS,
            USE_SESSION_TOKEN: USE_SESSION_TOKEN
        });

        // когда выбираем город определяем координаты и city place_id и передаем в объект дополнения адреса область поиска
        $('#city').on('suggestion', (e, locality) => {

            // сохраняем city place_id в #city data
            $('#city').data('city_id', locality.place_id);

            // осуществляем геокодирование места по G place_id
            location.getCoordinatesByPlaceIdPr(locality.place_id).then(coords => {
                // устанавливаем точку вокруг которой будут подсказки улиц
                suggestionAddr.lookingAround = {
                    latitude: coords.lat,
                    longitude: coords.lng
                };
            });
        });

        // когда фокус на поле ввода адреса, а город введен, но не выбран из подсказок (lookingAround - undefined)
        // определяем lookingAround и city place_id
        $('#address').focus(function (e) {
            const city = $('#city').val().trim();

            // запоминаем значение city для подсказок только по городу
            suggestionAddr.location.city = city;

            // если lookingAround не заполнен все равно заполняем его
            if (!suggestionAddr.lookingAround.latitude) {
                // если что-то введено ищем координаты
                if (city.length > 1) {
                    const countryG = $('#country').val();
                    // делаем геокодирование по городу
                    const addressObj = {
                        country: countryG,
                        address: city
                    }
                    location.getCoordinatesByAddressPr(addressObj).then(locality => {
                        // сохраняем city place_id в #city data
                        //$('#city').data('city', locality.place_id);
                        $('#city').data('city_id', locality.place_id);

                        // устанавливаем точку вокруг которой будут подсказки улиц
                        suggestionAddr.lookingAround = {
                            latitude: locality.lat,
                            longitude: locality.lng
                        };
                    });
                }
            }
        });

        // когда выбираем страну - заполняем поле public_country
        $('#country').change(function () {
            $('#public_country').val($('#country>option:selected').text());
        });

        // обнуляем lookingAround если вернулись на поле ввода города
        $('#city').focus(e => {
            suggestionAddr.lookingAround = {};
        });
    }

}