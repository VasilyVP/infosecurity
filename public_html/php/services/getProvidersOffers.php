<?php # Сервис передачи списка ЧОП с данными прейскуранта

    use utilities\funcsLib;
    use exceptions\UtilsException, exceptions\SPDMException;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // ответ по умолчанию
    $response = [
        'code' => 0,
        'data' => null,
        'message' => 'Unexpected error'
    ];

    // разбираем входные данные
    $where = $_POST['locality'] ?? false;
    $what = $_POST['search'] ?? false;

    $arr = [
        'page' => ['filter' => FILTER_VALIDATE_INT],
        'providersQuantity' => ['filter' => FILTER_VALIDATE_INT],
        'who' => ['filter' => FILTER_SANITIZE_STRING]
    ];
    $inputs = filter_input_array(INPUT_POST, $arr);

    $page = $inputs['page'];
    $providersQuantity = $inputs['providersQuantity'];
    $who = $inputs['who'];

    // НАДО СКОРРЕКТИРОВАТЬ ЭТИ ПАРАМЕТРЫ!!! бывает ошибка ???
    if (empty($where) || empty($what) || empty($page)) {
        $log->warning('Empty input parameters', ['METHOD' => __FILE__]);
        echo json_encode($response);
        exit;
    }

    // рассчитываем сколько ЧОП выводить на странице
    $providersByPage = false;
    if ($providersQuantity) $providersByPage = funcsLib::calcProvidersByPage($providersQuantity);

    // поисковые сервисы
    $services = [];
    // опциональные сервисы - датчики газа/воды
    $optServices = [];
    // прочие параметры
    $params = [];

    // разбираем параметры на сервисы и параметры (параметры не использую на самом деле) для будущей выборки
    foreach($what as $val) {
        // фильтруем, что не отфильтровали
        $val['name'] = filter_var($val['name'], FILTER_SANITIZE_STRING);
        $val['value'] = filter_var($val['value'], FILTER_SANITIZE_STRING);

        // если это показать все
        if ($val['name'] == 'service-type' && $val['value'] == 'all') $services[] = $val['value'];
        // по видам сервисов
        elseif ($val['name'] == 'service-type' && $val['value'] != 'signaling') $services[] = $val['value'];
        elseif ($val['name'] == 'connect-type' ) {
            if ($val['value'] == 'no_matter') {
                $services[] = 'wired';
                $services[] = 'wireless';
            } else $services[] = $val['value'];
        // и по доп параметрам
        } elseif ($val['name'] == 'water_leak' || $val['name'] == 'gas_leak') $optServices[] = $val['name'];
            elseif ($val['value'] != 'signaling') $params[$val['name']] = $val['value'];
    }

    // разбираем адресс full на компоненты и добавляем страну и координаты
    try {        
        $whereArr = funcsLib::parseCitytoArr($where['full']);
        $whereArr['country'] = $where['country'];
        if (isset($where['latitude'])) {
            $whereArr['latitude'] = $where['latitude'];
            $whereArr['longitude'] = $where['longitude'];    
        }
    } catch (UtilsException $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
        echo json_encode($response);
        exit;
    }

    try {
        // подключаем модель работы с ЧОП
        $spdm = \models\ServiceProvidersDataModel::create();

        // получаем список всех ЧОП в локации
        if (in_array('all', $services)) $data = $spdm->getProvidersOffersAll($whereArr, $page, $providersQuantity, $providersByPage);
        // получаем список ЧОП по видам
        else $data = $spdm->getProvidersOffersByConditions($whereArr, $services, $optServices, $page, $providersQuantity, $providersByPage);

        // подключаем модуль расчета стоимости
        $pce = new \engines\PriceCalculationEngine($what, $who);

        $providers = [];

        // если не известно количество на странице - рассчитываем
        $providersQuantity = $data->quantity;
        if (!$providersByPage) $providersByPage = funcsLib::calcProvidersByPage($providersQuantity);

        // формируем информацию для карточек результатов поиска
        for($i = 0, $c = min($providersByPage, count($data->providers)); $i < $c; $i++) {
            // текущая запись
            $provider = $data->providers[$i];

            // готовим объект для выдачи
            $providerOut = [
                'name' => html_entity_decode($provider->name),
                'phone' => $provider->phone,
            ];

            $prData = json_decode($provider->data);

            // если зарегистрированный ЧОП
            if ($prData) {
                $prPrice = json_decode($provider->price);

                // раскодируем html сущности перед вставкой в ответ запроса
                $prData = (object)funcsLib::htmlEntityArrDecode($prData);
                
                // вычисляем индекс главного адреса
                $primaryAddrN = array_search('primary', $prData->address_type);
                $providerOut['city'] = $prData->city[$primaryAddrN] ?? '';
                $providerOut['address'] = $prData->address[$primaryAddrN] ?? '';
                $providerOut['folder'] = $provider->id_client;
                $providerOut['provider_link'] = $provider->uid;
                $providerOut['logo_flag'] = $prData->logo_flag ?? false;                
                
                // проверяем прайс
                if ($prPrice) {
                    $offer = $pce->getOffer($prPrice);
                    $providerOut['price'] = $offer->price;
                    $providerOut['maintenance'] = $offer->maintenance;
                    $providerOut['currency'] = $offer->currency;
                    $providerOut['specification'] = $offer->specification;
                }
            // если загруженный ЧОП
            } else {
                $providerOut['city'] = $provider->data;
                $providerOut['address'] = $provider->price;
            }

            $providers[] = $providerOut;
        }

        $response = [
            'code' => 1,
            'data' => (object)[
                'providers' => $providers,
                'quantity' => $data->quantity,
                'providersByPage' => $providersByPage
            ]
        ];
    } catch (SPDMException $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
    } catch (Exception $e) {
        $response = [
            'code' => 2,
            'data' => 'no data'
        ];
    } catch (ErrorException $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
    } finally {
        echo json_encode($response);
    }
    