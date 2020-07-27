<?php ## Механизм отправки почтовых сообщений

    namespace engines;
    
    use \libriary\PHPMailer\PHPMailer;
    use \libriary\PHPMailer\Exception;

    class MailEngine
    {
        private $log;
        // результат возвращаемый функцией mail или PHPMailer
        private $status, $errorMessage = false;
        
        // параметры сообщения
        private $from, $fromName, $reply, $replyName, $to, $nameTo, $subject, $mailBody;
        private $template = '{MESSAGE}';
        // дополнительные заголовки письма для mail
        private $headers = [
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=utf-8"
        ];

        public function __construct()
        {
            $this->log = \engines\LogEngine::create();
        }

        // отправляет одно сообщение адресату посредством php mail()
        public function sendTo($mailData, $messageData)
        {
            // формируем параметры отправки сообщения
            $this->prepareData($mailData, $messageData);
       
            // отправляем письмо
            $this->status = mail($this->to, $this->subject, $this->mailBody, $this->headers);
            if (!$this->status) $this->log->error("Can't send email", ['METHOD' => __METHOD__]);
        }

        // отправляем одно письмо посредством PHPMailer
        public function sendToByPHPMailer($mailData, $messageData, $attachments = false)
        {
            // формируем параметры отправки сообщения
            $this->prepareData($mailData, $messageData);
            // создаем объект PHPMailer
            $mail = new PHPMailer;
            $mail->CharSet = 'UTF-8';
            // устанавливаем поля От и Ответить
            $mail->setFrom($this->from, $this->fromName);
            if ($this->reply !== false) $mail->addReplyTo($this->reply, $this->replyName);            
            // добавляем адресатов
            foreach($this->toMailer as $addr => $name) $mail->addAddress($addr, $name);
            
            // устанавливаем параметры PHPMailer
            $mail->isHTML(true);
            $mail->Subject = $this->subject;
            $mail->Body = $this->mailBody;
            
            //прикрепляем вложения
            if ($attachments) {
                foreach($attachments as $file => $fileName) $mail->addAttachment($file, $fileName);
            }

            //send the message, check for errors
            if (!$mail->send()) {
                $this->status = false;
                $this->errorMessage = $mail->ErrorInfo;
                $this->log->error("Can't send email", ['METHOD' => __METHOD__]);
            } else {
                $this->status = true;
            }
        }

        // возвращает статус отправки сообщения и обнуляет его
        public function getStatus()
        {
            $status = $this->status;
            $this->status = false;
            return $status;
        }

        public function getErrorMessage()
        {
            return $this->errorMessage;
        }

        // готовит данные для отправки сообщения
        private function prepareData($mailData, $messageData)
        {
            // заполняем From из опций
            $this->from = $mailData['from'] . '@' . DOMAIN_MAIL_NAME;
            $this->fromName = $mailData['fromName'] ?? '';
            // добавляем From в заголовки для mail
            $this->headers[] = 'From: ' . $this->fromName . ' <' . $this->from . '>';
            
            // формируем Headers для mail
            $this->headers = join("\r\n", $this->headers);
            
            // to - берет только первого адресата для mail()
            $this->to = array_keys($mailData['to'])[0];
            $this->nameTo = array_values($mailData['to'])[0];
            // toMailer отправляет всем адресатам через PHPMailer
            $this->toMailer = $mailData['to'];
            // проверяем Reply
            $this->reply = $mailData['reply'] ?? false;
            $this->replyName = $mailData['replyName'] ?? false;

            // тема
            $this->subject = $mailData['subject'];

            // если указан шаблон, то загружаем шаблон
            $template = $mailData['template'] ?? false;
            if ($template) $this->template = file_get_contents(TEMPLATES_EMAIL_PATH . '/' . $template);
        
            // заполняем шаблон
            $mail = new \engines\TemplateProcessing();
            $mail->setValues($messageData);
            //$mail->loadPage($this->template);
            $mail->parseTemplate($this->template);
            // формируем тело сообщения
            $this->mailBody = $mail->getPage();
        }

    }
