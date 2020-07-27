//import DetermineLocation from '/js/modules/DetermineLocation.js';
//import PlaceAutoCompliteG from '/js/modules/PlaceCompliteG.js';

console.log('href: ' + location.href);
console.log('hostname: ' + location.hostname);
console.log('pathname: ' + location.pathname);

$('.typeahead').typeahead({
    hint: false, // true does not work - это подсказки в поле ввода
    highlight: true,
    minLength: 1
},
    {
        templates: {
            footer: '<img src="imgs/logos/powered_by_google_on_white.png" style="float:right;">'
        },
        limit: 5, // 5 is default
        source: getMatches(),
        // определяем порядок отображения подсказок. suggestionObj - ппц объект по два набора на подсказку
        display: function (suggestionObj) {
            return suggestionObj.description;
        }
    });

function getMatches() {
    // запоминаем значения объекта
   // const countries = this.countries;
    
   console.log('in the getMatches');

    const country = 'Россия';

    return function findMatches(query, cb1, cb2) {
        // получаем countryID выбранной страны
       // const countryCode = countries[country];

       console.log('in the findMatches: ' + query);

        const countryCode = 'ru';

        console.log(PATHS.suggestionAPIurlG);

        // запрос JSON на подсказки
        $.getJSON(PATHS.suggestionAPIurlG, { input: query, country: countryCode }, response => {
            console.log(response);
            const matches = response.predictions;
            // костыль чтобы все подсказки отображались из-за бага в typeahead
            if (matches.length > 0) matches.push('');
            // возвращаем подсказки при асинхронном запросе. cb1 - для синхронного
            
            console.log('in the JSON request');

            cb2(matches);
        });
    };
}