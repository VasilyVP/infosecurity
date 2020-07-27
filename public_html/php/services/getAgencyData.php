<?php # Сервис передачи данных для страницы ЧОП

    use utilities\funcsLib;
    use models\ServiceProvidersDataModel;
    use exceptions\SPDMException;

    // загружаем пререквизиты
    require_once $_SERVER['DOCUMENT_ROOT'] . '/mandatory.php';

    // ответ по умолчанию
    $response = [
        'code' => 0,
        'data' => null,
        'message' => 'Unexpected error'
    ];

    // разбираем входные данные
    $uid = filter_input(INPUT_GET, 'link', FILTER_SANITIZE_STRING);

    try {
        // подключаем модель работы с ЧОП
        $spdm = ServiceProvidersDataModel::create();
        // получаем данные ЧОП

        $data = $spdm->getAgencyData($uid);

        if (!is_object($data)) throw new Exception('No page data exist');

        // раскодируем html сущности перед вставкой в ответ запроса
        $detailData = funcsLib::htmlEntityArrDecode(json_decode($data->detail_data));

        $response = [
            'code' => 1,
            'data' => $detailData,
            'folder' => $data->id_client
        ];
    } catch (SPDMException $e) {
        $log->error($e->getMessage(), ['METHOD' => __FILE__]);
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    } finally {
        echo json_encode($response);
    }
