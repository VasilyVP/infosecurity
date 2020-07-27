<?php # Сервис загрузки прейскуранта ЧОП

    use utilities\funcsLib;
    use exceptions\UtilsException;
    use models\UserDataModel;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // проверяем авторизацию
    $auth = \engines\AuthenticationEngine::create();
    if (!$auth->isAuthenticated()) {
        $log->warning('Attempt to send message without authorization', ['METHOD' => __FILE__]);
        exit();
    }

    // статус по умолчанию
    $status = [
        'status' => 'Error',
        'code' => 0,
        'message' => 'Unexpected error'
    ];

    // проверяем размер и тип входных данных
    try {
        $size = funcsLib::getStringsArrayMemSize($_POST);
        if ($size > MAX_MEM_CLIENT_STRING_DATA) throw new Exception("Too big input data size = $size byte");
    } catch (Exception $e) {
        // если в данных не только строки или превышен размер
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
        exit();
    }

    // формируем массив переменных для фильтрации
    $args = [];
    foreach($_POST as $var => $value) {
        $args[$var] = FILTER_VALIDATE_FLOAT;
    }
    $args['price_currency'] = FILTER_SANITIZE_STRING;

    // фильтруем массив и удаляем пустые элементы, но сохраняем 0
    $inputs = filter_input_array(INPUT_POST, $args);
    $inputs = array_filter($inputs, function($el) {
        if ($el === false) return false;
        return true;
    });

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
            //$request = true;
        } else {
            //$request = MODERATION_EVERY_CHANGE ? true : false;
        }
        // если не создали - выходим
        if (!$chopID) throw new Exception("Can't create user_provider folder or record for user '$userID'.");

        // сохраняем данные и передаем требуется ли модерация
        if (!$spdm->savePriceData($chopID, $inputs)) throw new Exception("Can't save Price data");

        // формируем положительный статус
        $status = [
            'status' => 'Ok',
            'code' => 1,
            'message' => 'Price successfully saved'
        ];
    } catch (Exception $e) {
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
    } finally {
        echo json_encode($status);
    }
