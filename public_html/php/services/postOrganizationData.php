<?php # Сервис загрузки данных ЧОП

    use utilities\funcsLib;
    use exceptions\UtilsException;
    use models\UserDataModel;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // статус по умолчанию
    $status = [
        'status' => 'Error',
        'code' => 0,
        'message' => 'Unexpected error'
    ];

    // проверяем авторизацию
    $auth = \engines\AuthenticationEngine::create();
    if (!$auth->isAuthenticated()) {
        // если нет, то шлем соотв ответ
        $status['code'] = 2;
        $status['message'] = 'Failed authorization';
        echo json_encode($status);        
        exit();
    }

    // проверяем размер и тип входных данных
    try {
        $size = funcsLib::getStringsArrayMemSize($_POST);
        if ($size > MAX_MEM_CLIENT_STRING_DATA) throw new Exception("Too big input data size = $size byte");
    } catch (Exception $e) {
        // если в данных не только строки или превышен размер
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
        exit();
    }

    // фильтруем входные данные и удаляем пустые значения
    $args = [
        'organization' => FILTER_SANITIZE_STRING,
        'brand_name' => FILTER_SANITIZE_STRING,
        'phone_code' => FILTER_SANITIZE_STRING,
        'main_phone' => FILTER_SANITIZE_STRING,
        'chop_about' => FILTER_SANITIZE_STRING,
        'logo_flag' => FILTER_SANITIZE_STRING, //FILTER_VALIDATE_BOOLEAN
        'chop_photo_file_name' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'chop_photo_name' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'chop_licence_file_name' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'chop_licence_name' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'feedback_name' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'feedback_file_name' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'feedback_client_name' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'feedback_content' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'country' => FILTER_SANITIZE_STRING,
        'public_country' => FILTER_SANITIZE_STRING,
        'city_id' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'city' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'address' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'address_type' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'office' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'latitude' => [
            'filter' => FILTER_VALIDATE_FLOAT,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'longitude' => [
            'filter' => FILTER_VALIDATE_FLOAT,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'client_item' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'why_you_item' => [
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_REQUIRE_ARRAY
        ],
        'service_about' => FILTER_SANITIZE_STRING,
        'chop_site' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^(\S+\.)+\S{2,10}(\S+)*$/']
        ],
        'chop_email' => FILTER_VALIDATE_EMAIL,
        'establishment_year' => FILTER_VALIDATE_INT,
        'clients_quantity' => FILTER_VALIDATE_INT,
        'employee_quantity' => FILTER_VALIDATE_INT,
        'gbr_quantity' => FILTER_VALIDATE_INT,
        
        'design_installation_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'pult_security_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'physic_security_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'cctv_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'gps_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'collection_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'cargo_escort_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'access_control_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'service_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'wired_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'wireless_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'gas_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ],
        'water_check' => [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => '/^on$/']
        ]
    ];
    // фильтруем массив и удаляем пустые элементы
    $inputs = array_filter(filter_input_array(INPUT_POST, $args));    

    // и дополнительно удаляем пустые элементы массивов
    $inputs = array_map(function($el) {
        // удаляем первые пустые/false элементы в массивах (шаблоны)
        if (is_array($el)) {
            if (!$el[0]) array_shift($el);
        }
        return $el;
    }, $inputs);

    // получаем userID
    $userRole = $auth->getUserRole();
    if ($userRole == 'admin' || $userRole == 'moderator') {
        // если админ или модератор
        $userLogin = filter_var($_COOKIE['userLogin'] ?? false, FILTER_VALIDATE_EMAIL);

        $udm = UserDataModel::create();
        $userDataByEmail = $udm->getUserDataByEmail($userLogin);        
        // проверяем, если перешли на страницу временных ЧОП(а ее бть не может и такого id_user нет)
        if (is_object($userDataByEmail)) $userID = $userDataByEmail->id_user;
        else {
            echo json_encode($status);
            exit();
        }
    } else {
        // если user
        $userID = $auth->getUserID();
    }

    // проверяем есть ли запись ЧОПа в базе и если нет - заводим
    // сохраняем записи в БД
    try {
        // подключаем модель работы с ЧОП
        $spdm = \models\ServiceProvidersDataModel::create();

        // получаем ID ЧОПа
        $chopID = $spdm->getServiceProviderByUserID($userID);
        // определяем нужен ли запрос, а также если записи ЧОП еще нет, то заводим ее
        if (!$chopID) {
            $chopID = $spdm->createServiceProviderRecord($userID);
            $request = true;
        } else {
            $request = MODERATION_EVERY_CHANGE ? true : false;
        }
        // если не создали - выходим
        if (!$chopID) throw new Exception("Can't create user_provider folder or record for user '$userID'.");

        // сохраняем данные и передаем требуется ли модерация
        if (!$spdm->saveOrganizationData($chopID, $inputs, $request)) throw new Exception("Can't save Organization data");

        // формируем положительный статус
        $status = [
            'status' => 'Ok',
            'code' => 1,
            'message' => 'Data successfully saved',
            'id' => md5($chopID . $inputs['brand_name'])
        ];
    } catch (Exception $e) {
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
    } finally {
        echo json_encode($status);
    }
