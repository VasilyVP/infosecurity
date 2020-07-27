<?php # Сервис отправки сообщения через "Связаться с нами"
    use engines\CheckRobots;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';
    
    // проверяем reCAPTCHA
    $token = $_POST['captcha_contact_token'] ?? false;
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
    $check = $cap->getCheckByCaptcha('contact', $token);
    // если робот - логируем, шлем ответ и выходим
    if ($check->status == 'robot') {
        $log->warning("Registration attempt by robot detected. Score: $check->score", ['METHOD' => __FILE__]);
        // отправляем ответ и выходим
        echo json_encode($status);
        exit();
    }

    // фильтруем входные параметры
    $inputs = filter_input_array(INPUT_POST, [
        'email' => ['filter' => FILTER_VALIDATE_EMAIL],
        'reason' => ['filter' => FILTER_SANITIZE_STRING],
        'subject' => ['filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS],
        'message' => ['filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS]
    ]);

    // статус, ошибки
    $status = [
        'status' => 'Error',
        'code' => 2,
        'message' => 'Probably email is invalid'
    ];
    // если пустой - выходим
    if (!is_array($inputs) || in_array(false, $inputs)) {
        echo json_encode($status);
        exit();
    }    

    // формируем и проверяем входные данные
    $email = $inputs['email'] ?? false;
    $reason = $inputs['reason'] ?? false;
    $subject = $inputs['subject'] ?? false;
    $message = $inputs['message'] ?? false;
    
    // проверяем поле reason и формируем адрес
    if ($reason == 'support') $mailTo = [ SUPPORT_EMAIL => SUPPORT_NAME ];
    elseif ($reason == 'sales') $mailTo = [ SALES_EMAIL => SALES_NAME ];
    else {
        $log->warning('Unexpected "reason" field value', ['METHOD' => __FILE__]);
        exit();
    }
    
    // проверяем поля сообщения
    if (mb_strlen($subject) > 50 || mb_strlen($message > 500)) {
        $log->warning('Too big message field', ['METHOD' => __FILE__]);
        exit();
    }
    
    // формируем сообщение
    $message = nl2br($message, false);

    // подключаем почтовый engine
    $mail = new \engines\MailEngine();

    // отправляем письмо
    $mail->sendToByPHPMailer([
        'from' => 'robot',
        //'fromName' => "$name $patronymic $surname",
        'reply' => $email,
        //'replyName' => "$name $surname",
        'to' => $mailTo,
        'subject' => $subject,
    //    'template' => TEMPLATES_EMAIL_PATH . '/'
    ], [
        'FROM' => $email,
        'MESSAGE' => $message
    ]);

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
