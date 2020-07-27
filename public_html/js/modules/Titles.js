/** Класс формирования заголовков */
import SeoMap from '/js/modules/SeoMap.js';

export default class Titles {

    constructor() {
        const seoMap = new SeoMap();
        
        this.seoMap = seoMap.mapTitles;
        this.citiesMap = seoMap.citiesMap;
    }

    getCaption(input) {

        const search0 = input.search[0] || false;// ? input.search[0].value : false;
        const search1 = input.search[1] || false;
        const search2 = input.search[2] || false;// ? input.search[2].value : false;
        //const search3 = input.search[3] || false;// ? input.search[3].value : false;
        //const search4 = input.search[4] || false;// ? input.search[4].value : false;
        
        let mapName = '';
        const options = Object.values(input.search).map(el => el.name);

        switch (search0.value) {
            case 'signaling':
                mapName = 'signaling';
                // если для физиков (есть место сигнализации)
                if (search2.name == 'signaling-place-type') {
                    // добавляем признак физиков и место сигнализации (flat/house/garage)
                    mapName += '_phys_' + search2.value;

                    if (options.includes('sec_signaling')) mapName += '_sec';
                    if (options.includes('fire_signaling')) mapName += '_fire';
                    if (options.includes('alarm_button')) mapName += '_btn';
                    if (options.includes('glass_break')) mapName += '_glass';
                    if (options.includes('water_leak')) mapName += '_water';
                    if (options.includes('gas_leak')) mapName += '_gas';

                // если юрики
                } else {
                    mapName += '_jur';
                    if (options.includes('sec_signaling')) mapName += '_sec';
                    if (options.includes('fire_signaling')) mapName += '_fire';
                    if (options.includes('alarm_button')) mapName += '_btn';
                }

                break;
            case 'CCTV':
                mapName = 'cctv_' + search1.value;

                break;
            case 'guard':
                // если физики
                if (options.length == 2) mapName = 'guard';
                // если для Юриков
                else mapName = 'guard_' + search1.value + '_' + search2.value;

                break;
            case 'GPS':
                if ($('#nav-phys').hasClass('active')) mapName = 'gps_phys';
                else mapName = 'gps_jur';
        
                break;
            case 'cargo_escort':
                mapName = 'cargo_' + search1.value;
                break;
            case 'collection':
                mapName = 'collection';
                break;
            case 'access_control':
                mapName = 'access_control';
                break;
            case 'maintenance':
                mapName = 'maintenance';
                break;
            case 'design_installation':
                mapName = 'design_installation';
                break;
            default:
                mapName = 'all_providers';
                break;
        }

        const seoObj = this.seoMap[mapName];
        
        // если город есть среди склоняемых - меняем на него иначе оставляем его как есть
        const city = this.citiesMap.hasOwnProperty(input.locality.full) ? this.citiesMap[input.locality.full] : input.locality.full;

        seoObj.caption = seoObj.caption.replace('{CITY}', city);
        seoObj.description = seoObj.description.replace('{CITY}', city);
        
        return seoObj;
    }

}