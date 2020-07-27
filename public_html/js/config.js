export {PATHS, KEYS, USE_SESSION_TOKEN, PARAMS};
// Конфигурационный файл JS
// PATHS
const PATHS = {
    suggestionAPIurlG: location.protocol + '//' + location.hostname + '/php/services/getSuggestion.php',
    userRegistrationAPIurl: location.protocol + '//' + location.hostname + '/php/services/registerUser.php',
    userLoginAPIurl: location.protocol + '//' + location.hostname + '/php/services/loginUser.php',
    userUpdateAPIurl: location.protocol + '//' + location.hostname + '/php/services/userUpdate.php',
    supportSendAPIurl: location.protocol + '//' + location.hostname + '/php/services/supportSend.php',
    userPasswResetAPIurl: location.protocol + '//' + location.hostname + '/php/services/resetPassw.php',
    userNewPasswAPIurl: location.protocol + '//' + location.hostname + '/php/services/setNewPassw.php',
    getUsersListAPIurl: location.protocol + '//' + location.hostname + '/php/services/getUsersList.php',
    setUserRoleStatusAPIurl: location.protocol + '//' + location.hostname + '/php/services/setUserRoleStatus.php',
    getClientsListAPIurl: location.protocol + '//' + location.hostname + '/php/services/getClientsList.php',
    getCitiesListAPIurl: location.protocol + '//' + location.hostname + '/php/services/getCitiesList.php',
    setClientStatusAPIurl: location.protocol + '//' + location.hostname + '/php/services/setClientStatus.php',
    organizationFilesLoadAPIurl: location.protocol + '//' + location.hostname + '/php/services/postOrganizationFiles.php',
    organizationDataLoadAPIurl: location.protocol + '//' + location.hostname + '/php/services/postOrganizationData.php',
    priceDataLoadAPIurl: location.protocol + '//' + location.hostname + '/php/services/postPriceData.php',
    getOrgPriceDataAPIurl: location.protocol + '//' + location.hostname + '/php/services/getOrgPriceData.php',
    getProvidersOffersAPIurl: location.protocol + '//' + location.hostname + '/php/services/getProvidersOffers.php',
    getAgencyDataAPIurl: location.protocol + '//' + location.hostname + '/php/services/getAgencyData.php',
    providerRequestsAPIurl: location.protocol + '//' + location.hostname + '/php/services/providerRequests.php',
    reCAPCHAverifyAPIurl: location.protocol + '//' + location.hostname + '/php/services/getReCAPCHAcheck.php',
    mailingsAPIurl: location.protocol + '//' + location.hostname + '/php/services/mailingsAPI.php',
    contactSendAPIurl: location.protocol + '//' + location.hostname + '/php/services/contactSend.php',
    geoAPIurl: 'https://maps.googleapis.com/maps/api/geocode/json'
};

const KEYS = {
    geoAPIkeyG: XXX,
    reCAPTCHAsiteKey: XXX
};

// Использовать session_token в G Autocomplite
const USE_SESSION_TOKEN = true;

const PARAMS = {
    MAX_PAGES: 8
};

//export {PATHS, KEYS, USE_SESSION_TOKEN};