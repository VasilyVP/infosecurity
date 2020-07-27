<?php # Сервис отправки сообщения на поддержку
    use engines\CheckRobots;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';
    
    // проверяем reCAPTCHA
    $token = $_POST['captcha_support_token'] ?? false;
    // статус, что робот
    $status = [
        'status' => 'Warning',
        'code' => 2,
        'message' => 'You are robot'
    ];
    // если токена нет отправляем ответ и выходим
    if (!$token) {
        $log->warning("There's no reCAPTCHA token", ['METHOD' => __FILE__]);
        // отправляем ответ и выходим
        echo json_encode($status);
        exit();
    }
    
    // проверяем токен
    $cap = new CheckRobots();
    $check = $cap->getCheckByCaptcha('support', $token);
    // если робот - логируем, шлем ответ и выходим
    if ($check->status == 'robot') {
        $log->warning("Registration attempt by robot detected. Score: $check->score", ['METHOD' => __FILE__]);
        // отправляем ответ и выходим
        echo json_encode($status);
        exit();
    }

    // проверяем авторизацию
    $auth = \engines\AuthenticationEngine::create();
    if (!$auth->isAuthenticated()) {
        $log->warning('Attempt to send message without authorization', ['METHOD' => __FILE__]);
        exit();
    }

    // фильтруем входные параметры
    $inputs = filter_input_array(INPUT_POST, [
        'name' => ['filter' => FILTER_SANITIZE_STRING],
        'surname' => ['filter' => FILTER_SANITIZE_STRING],
        'patronymic' => ['filter' => FILTER_SANITIZE_STRING],
        'subject' => ['filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS],
        'message' => ['filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS]
    ]);

    // если пустой - выходим
    if (!is_array($inputs)) exit();

    // формируем и проверяем входные данные
    $name = $inputs['name'] ?? false;
    $surname = $inputs['surname'] ?? false;
    $patronymic = $inputs['patronymic'] ?? false;
    $subject = $inputs['subject'] ?? false;
    $message = $inputs['message'] ?? false;
    
    // проверяем поля сообщения
    if (mb_strlen($subject) > 50 || mb_strlen($message > 500) || mb_strlen($name > 20) || mb_strlen($surname) > 20 || mb_strlen($patronymic) > 20) {
        $log->warning('Too big message field', ['METHOD' => __FILE__]);
        exit();
    }

    $attachments = [];
    $totalSize = 0;
    foreach($_FILES as $file) {
        $attachments[$file['tmp_name']] = $file['name'];
        // считаем размер вложений
        $totalSize += $file['size'];
    }
    // проверяем размер вложений
    if ($totalSize > 8300000) {
        $log->warning('Too big attachments', ['METHOD' => __FILE__]);
        exit();
    }
    
    // формируем сообщение
    $message = nl2br($message, false);

    // подключаем почтовый engine
    $mail = new \engines\MailEngine();

    // отправляем письмо
    $mail->sendToByPHPMailer([
        'from' => 'robot',
        'fromName' => "$name $patronymic $surname",
        'reply' => $auth->getUserLogin(),
        'replyName' => "$name $surname",
        'to' => [ SUPPORT_EMAIL => SUPPORT_NAME ],
        'subject' => $subject,
    //    'template' => TEMPLATES_EMAIL_PATH . '/'
    ], [
        'FROM' => "$name $patronymic $surname " . $auth->getUserLogin(),
        'MESSAGE' => $message
    ], $attachments);

    // если письмо ушло
    if ($mail->getStatus()) {
        $status = [
            'status' => 'Ok',
            'code' => 1,
            'message' => 'email sent'
        ];
    } else {
        $errorMsg = $mail->getErrorMessage();
        $status = [
            'status' => 'Error',
            'code' => 0,
            'message' => $errorMsg
        ];
        $log->warning("Mail function doesn't work: " . $errorMsg, ['METHOD' => __FILE__]);
    }

    echo json_encode($status);
