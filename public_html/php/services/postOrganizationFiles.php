<?php # Сервис загрузки и удаления файлов на сервер

    use exceptions\MDMException;
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

    // получаем userID
    $userRole = $auth->getUserRole();
    if ($userRole == 'admin' || $userRole == 'moderator') {
        // если админ или модератор
        $userLogin = filter_var($_COOKIE['userLogin'] ?? false, FILTER_VALIDATE_EMAIL);

        $udm = UserDataModel::create();
        $userID = $udm->getUserDataByEmail($userLogin)->id_user;
    } else {
        // если user
        $userID = $auth->getUserID();
    }

    // подключаем модель работы с ЧОП
    try {
        $spdm = \models\ServiceProvidersDataModel::create();
    } catch (Exception $e) {
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
        echo json_encode($status);
    }
    // если удаляем файл
    $deleteFile = $_POST['deleteFile'] ?? false;
    if ($deleteFile) {
        try {
            // получаем chopID (папку)
            $chopID = $spdm->getServiceProviderByUserID($userID);
            if (!$chopID) throw new Exception("Can't get chopID to delete file");
            
            if ($spdm->deleteFile($chopID, $deleteFile)) {
                $status = [
                    'status' => 'Success',
                    'code' => 1,
                    'message' => "File $deleteFile has been deleted"
                ];
            }
        } catch (Exception $e) {
            $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
            $status = [
                'status' => 'Error',
                'code' => 0,
                'message' => "$e"
            ];
        } finally {
            echo json_encode($status);
            exit();
        }
    }

    // если загружаем файл
    $file = $_FILES['logo'] ?? $_FILES['photo'] ?? $_FILES['licence'] ?? $_FILES['feedback'] ?? false;
    // проверяем файл
    try {
        if (!$file) {
            $status = [
                'status' => 'Error',
                'code' => 0,
                'message' => 'No file',
            ];
            throw new Exception($status['message']);
        } elseif ($file['size'] > IMAGE_MAX_SIZE) {
            $status = [
                'status' => 'Error',
                'code' => 0,
                'message' => 'File size is too big: ' . $file['size'] . ' byte.',
            ];
            throw new Exception($status['message']);
        }
    } catch (Exception $e) {
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
        echo json_encode($status);
        exit();
    }

    try {
        // получаем ID ЧОПа
        $chopID = $spdm->getServiceProviderByUserID($userID);

        // если записи ЧОП еще нет, то заводим ее и создаем папку
        if (!$chopID) $chopID = $spdm->createServiceProviderRecord($userID);
        // если не создали - выходим
        if (!$chopID) throw new Exception("Can't create user ($userID) folder or service_provider record");        

        // формируем и проверяем имя файла
        if (isset($_FILES['logo'])) {
            $fileName = 'logo';
        } else {
            $fileName = basename($file['name']);

            // проверяем что файл картинка или pdf
            $prM = preg_match('/.+\.(jpe?g|png|pdf)$/iu', $fileName);
            if ( $prM !== 1) throw new Exception('Invalid file name');
        }

        // проверяем количество файлов в папке ЧОП
        if ($fileName != 'logo' && $spdm->getFilesQuantity($chopID) == MAX_FILES_QUANTITY) {
            throw new Exception("Files quantity limit reached");
        }

        // проверяем существует ли уже такой файл и если нет - перемещаем его
        if ($fileName != 'logo' && file_exists(SERVICE_PROVIDERS_IMGS_PATH . "/$chopID/$fileName"))
            throw new MDMException("File allready exist's");
        if (!move_uploaded_file($file['tmp_name'], SERVICE_PROVIDERS_IMGS_PATH . "/$chopID/$fileName")) {
            throw new Exception("Can't copy uploaded logo");
        }

        // формируем положительный ответ
        $status = [
            'status' => 'Ok',
            'code' => 1,
            'message' => "File added",
        ];
    } catch (MDMException $e) {
        // если файл уже есть
        $status = [
            'status' => 'Error',
            'code' => 2,
            'message' => $e->getMessage(),
        ];
    } catch (Exception $e) {
        $log->warning($e->getMessage(), ['METHOD' => __FILE__]);
        $status = [
            'status' => 'Error',
            'code' => 0,
            'message' => $e->getMessage(),
        ];
    } finally {
        echo json_encode($status);
    }
