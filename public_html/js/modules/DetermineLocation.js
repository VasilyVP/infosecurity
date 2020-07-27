// Модуль определения географических координат и наименования местоположения;
export default class DetermineLocation {
    // инициирует опеределение местоположения и обратное геокодирование
    // в locationPr возвращает Promise с результатом location
    constructor(params) { // params = {coords, place: boolean}
        // запоминаем this
        const thisObj = this;

        // проверяем есть ли навигация
        /*
        if (!navigator.geolocation) {
            this.navAble = false;
        } else this.navAble = true;
        */
       
        this.KEYS = params.KEYS;
        this.PATHS = params.PATHS;

        // создаем объект для хранения
        this.location = {}
        // здесь собираем свойства объекта
        const location = {};

        // если требуется сразу определить геокоординаты
        if (params.coords) {
            // localityPr содержит Promise с результатами обратного геокодирования в location obj
            this.locationPr = new Promise((resolve, reject) => {

                // когда разрешится Promise с геокоординатами getCoordinatsPr
                this.getCoordinatsPr().then(position => {
                    location.latitude = position.coords.latitude;
                    location.longitude = position.coords.longitude;
                    // запоминаем результат в объекте
                    thisObj.location = location;

                    // если определяем еще и детали местоположения
                    if (params.place) {
                        // ждем разрешения Promise c обратным геокодированием по этим координатам
                        thisObj.getReverseGeocodingPr(location.latitude, location.longitude).then(locality => {
                            location.city = locality.city || null;
                            location.area = locality.area || null;
                            location.region = locality.region || null;
                            location.country = locality.country || null;
                            location.status = locality.status || null;
                            // запоминаем результат в объекте
                            thisObj.location = location;
                            // выполняем Promise locationPr с итоговыми результатами обратного геокодирования
                            resolve(location);
                        });
                    } else resolve(location);
                });
            });
        }
    }

    // возвращает Promise c результатами обратного геокодирования
    getReverseGeocodingPr(latitude, longitude) {
        const thisObj = this;

        // локальный location который будем возвращать в Promise
        const location = {};
        location.latitude = latitude;
        location.longitude = longitude;
        // параметры запроса обратного геокодирования Google API
        const params = {
            latlng: latitude + ',' + longitude,
            language: 'ru',
            key: this.KEYS.geoAPIkeyG // константа из config
        };
        // возвращаем Promise
        return new Promise((resolve, reject) => {
            // запрос обратного геокодирования в G
            $.getJSON(thisObj.PATHS.geoAPIurl, params, response => {
                // сохраняем статус геоопределения                
                location.status = response.status;
                // формируем компоненты адреса
                if (response.status == "OK") {
                    location.place_id = response.results[0].place_id;
                    const addrComps = response.results[0].address_components;
                    addrComps.forEach(addrComp => {
                        if (addrComp.types.some(el => el == 'locality')) location.city = addrComp.long_name;
                        if (addrComp.types.some(el => el == 'administrative_area_level_2')) location.area = addrComp.long_name;
                        if (addrComp.types.some(el => el == 'administrative_area_level_1')) location.region = addrComp.long_name;
                        if (addrComp.types.some(el => el == 'country')) location.country = addrComp.long_name;
                    });
                }
                // выполняем Promise c возвращенными координатами
                resolve(location);
            });
        });
    }

    // возвращает Promise с геокоординатами
    getCoordinatsPr() {
        if ("geolocation" in navigator) {
            return new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(position => {
                    resolve(position);
                });
            });
        }
    }

    // возвращает Promise с координатами по Google place_ID
    getCoordinatesByPlaceIdPr(placeID) {
        const thisObj = this;

        return new Promise((resolve, reject) => {
            // определяем координаты
            const params = {
                place_id: placeID,
                language: 'ru',
                key: thisObj.KEYS.geoAPIkeyG
            };
            $.getJSON(thisObj.PATHS.geoAPIurl, params, response => {
                if (response.status == "OK") {
                    // координаты
                    const coords = response.results[0].geometry.location;
                    resolve(coords); // coords is obj {lat, lng}
                }
            });
        });
    }

    // возвращает Promise с координатами по адресу
    getCoordinatesByAddressPr(addressObj) {
        const thisObj = this;

        const $country = addressObj.country;
        const $address = addressObj.address;

        return new Promise((resolve, reject) => {
            // определяем координаты
            const params = {
                address: $address,
                language: 'ru',
                components: 'country:' + $country,
                key: thisObj.KEYS.geoAPIkeyG
            };
            $.getJSON(thisObj.PATHS.geoAPIurl, params, response => {
                if (response.status == "OK") {
                    // координаты
                    const locality = response.results[0].geometry.location;
                    locality.place_id = response.results[0].place_id;
                    resolve(locality); // coords is obj {lat, lng}
                }
            });
        });
    }
}
