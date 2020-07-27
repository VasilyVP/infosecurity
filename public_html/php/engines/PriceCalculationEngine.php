<?php /** Engine формирования стоимости услуги и абонентской платы по параметрам поиска */

namespace engines;

class PriceCalculationEngine
{
    private $map;
    private $optionsPhys;
    private $optionsOrg;
    private $services;
    private $who;
    private $price;
    private $calcPr = 0;
    private $calcMaint = 0;
    private $noPriceFilled = false;
    private $specification;

    public function __construct($what, $who)
    {
        $this->log = \engines\LogEngine::create();
        // запоминаем требуемые сервисы и опции
        $this->who = $who;

        // разбираем параметры запроса what
        $this->parseWhat($what);

        // соответствие сервисов и опций полям калькуляции в прейскуранте price
        $this->map = [
            'signaling' => [
                'nav-phys' => [
                    'flat' => [
                        'wired' => [
                            'price' => 'signal_flat_wired_price',
                            'maintenance' => 'signal_flat_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'signal_flat_wireless_price',
                            'maintenance' => 'signal_flat_wireless_maintenance',
                        ],
                    ],
                    'house' => [
                        'wired' => [
                            'price' => 'signal_house_wired_price',
                            'maintenance' => 'signal_house_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'signal_house_wireless_price',
                            'maintenance' => 'signal_house_wireless_maintenance',
                        ],
                    ],
                    'garage' => [
                        'wired' => [
                            'price' => 'signal_garage_wired_price',
                            'maintenance' => 'signal_garage_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'signal_garage_wireless_price',
                            'maintenance' => 'signal_garage_wireless_maintenance',
                        ],
                    ],
                    'fire_signaling' => [
                        'wired' => [
                            'price' => 'smoke_det_wired_price',
                            'maintenance' => 'smoke_det_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'smoke_det_wireless_price',
                            'maintenance' => 'smoke_det_wireless_maintenance',
                        ],
                    ],
                    'alarm_button' => [
                        'wired' => [
                            'price' => 'alarm_btn_opt_wired_price',
                            'maintenance' => 'alarm_btn_opt_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'alarm_btn_opt_wireless_price',
                            'maintenance' => 'alarm_btn_opt_wireless_maintenance',
                        ],
                    ],
                    'water_leak' => [
                        'wired' => [
                            'price' => 'water_det_wired_price',
                            'maintenance' => 'water_det_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'water_det_wireless_price',
                            'maintenance' => 'water_det_wireless_maintenance',
                        ],
                    ],
                    'gas_leak' => [
                        'wired' => [
                            'price' => 'gas_det_wired_price',
                            'maintenance' => 'gas_det_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'gas_det_wireless_price',
                            'maintenance' => 'gas_det_wireless_maintenance',
                        ],
                    ],
                    'glass_break' => [
                        'wired' => [
                            'price' => 'glass_det_wired_price',
                            'maintenance' => 'glass_det_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'glass_det_wireless_price',
                            'maintenance' => 'glass_det_wireless_maintenance',
                        ],
                    ],

                ],
                'nav-org' => [
                    'sec_signaling' => [
                        'wired' => [
                            'price' => 'signal_sec_business_wired_price',
                            'maintenance' => 'signal_sec_business_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'signal_sec_business_wireless_price',
                            'maintenance' => 'signal_sec_business_wireless_maintenance',
                        ],
                    ],
                    'fire_signaling' => [
                        'wired' => [
                            'price' => 'signal_fire_business_wired_price',
                            'maintenance' => 'signal_fire_business_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'signal_fire_business_wireless_price',
                            'maintenance' => 'signal_fire_business_wireless_maintenance',
                        ],
                    ],
                    'sec_fire_signaling' => [
                        'wired' => [
                            'price' => 'signal_compl_business_wired_price',
                            'maintenance' => 'signal_compl_business_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'signal_compl_business_wireless_price',
                            'maintenance' => 'signal_compl_business_wireless_maintenance',
                        ],
                    ],
                    'alarm_button' => [
                        'wired' => [
                            'price' => 'alarm_btn_wired_price',
                            'maintenance' => 'alarm_btn_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'alarm_btn_wireless_price',
                            'maintenance' => 'alarm_btn_wireless_maintenance',
                        ],
                    ],
                    'alarm_button_opt' => [
                        'wired' => [
                            'price' => 'alarm_btn_opt_wired_price',
                            'maintenance' => 'alarm_btn_opt_wired_maintenance',
                        ],
                        'wireless' => [
                            'price' => 'alarm_btn_opt_wireless_price',
                            'maintenance' => 'alarm_btn_opt_wireless_maintenance',
                        ],
                    ],
                ],
            ],
            'CCTV' => [
                'flat' => [
                    'price' => 'cctv_flat_price',
                    'maintenance' => 'cctv_flat_maintenance',
                ],
                'house' => [
                    'price' => 'cctv_house_price',
                    'maintenance' => 'cctv_house_maintenance',
                ],
                'entrance' => [
                    'price' => 'cctv_entrance_price',
                    'maintenance' => 'cctv_entrance_maintenance',
                ],
                'office' => [
                    'price' => 'cctv_office_price',
                    'maintenance' => 'cctv_office_maintenance',
                ],
                'shop' => [
                    'price' => 'cctv_shop_price',
                    'maintenance' => 'cctv_shop_maintenance',
                ],
                'warehouse' => [
                    'price' => 'cctv_stock_price',
                    'maintenance' => 'cctv_stock_maintenance',
                ],
                'car_wash' => [
                    'price' => 'cctv_car_wash_price',
                    'maintenance' => 'cctv_car_wash_maintenance',
                ],
            ],
            'guard' => [
                'nav-phys' => [
                    'unarmed' => 'private_sec_unarmed',
                    'armed' => 'private_sec_armed',
                ],
                'nav-org' => [
                    'dayly' => [
                        'unarmed' => 'guard_day_unarmed',
                        'armed' => 'guard_day_armed',
                    ],
                    'nightly' => [
                        'unarmed' => 'guard_night_unarmed',
                        'armed' => 'guard_night_armed',
                    ],
                    '24' => [
                        'unarmed' => 'guard_24_unarmed',
                        'armed' => 'guard_24_armed',
                    ],
                ],
            ],
            'cargo_escort' => [
                'unarmed' => 'cargo_escort_unarmed',
                'armed' => 'cargo_escort_armed',
            ],
            'GPS' => [
                'nav-phys' => [
                    'price' => 'gps_private_price',
                    'maintenance' => 'gps_private_maintenance',
                ],
                'nav-org' => [
                    'price' => 'gps_commercial_price',
                    'maintenance' => 'gps_commercial_maintenance',
                ],
            ],
        ];

        // список опций с их наименованиями для клиентов
        $this->optionsPhys = [
            'fire_signaling' => 'Датчик дыма',
            'alarm_button' => 'Тревожная кнопка',
            'water_leak' => 'Датчик протечки воды',
            'gas_leak' => 'Датчик газа',
            'glass_break' => 'Датчик разбития стекла'
        ];
        $this->optionsOrg = [
            'sec_signaling' => 'Охранная сигнализация для бизнеса',
            'fire_signaling' => 'Пожарная сигнализация для бизнеса',
            'sec_fire_signaling' => 'Охранно-пожарная сигнализация для бизнеса',
            'alarm_button' => 'Сигнализация: Тревожная кнопка для бизнеса',
            'alarm_button_opt' => 'Тревожная кнопка'
        ];
    }

    /** Разбирает параметры what на ключ->значение*/
    private function parseWhat($what)
    {
        foreach ($what as $val) {
            $this->services[$val['name']] = $val['value'];
        }
    }

    /** Возвращает стоимость услуги */
    public function getOffer($price)
    {
        // запоминаем прайс
        $this->price = $price;

        // обнуляем стоимости в объекте, признак наличия незаполненного поля и спецификацию
        $this->calcPr = 0;
        $this->calcMaint = 0;
        $this->noPriceFilled = false;
        $this->specification = [];

        $srv = $this->services;
        $who = $this->who;
        $map = $this->map;

        $srv_type = $srv['service-type'];
        $srv_place = $srv['signaling-place-type'] ?? false;
        $srv_connect = $srv['connect-type'] ?? false;
        // если тип соединения не важен - устанавливаем на проводной
        if ($srv_connect == 'no_matter') {
            $srv_connect = 'wired';
        }

        // сигнализация
        if ($srv_type == 'signaling') {
            // для физиков
            if ($who == 'nav-phys') {
                // считаем базовый набор
                $price_adr = $map[$srv_type][$who][$srv_place][$srv_connect]['price'];
                $maint_adr = $map[$srv_type][$who][$srv_place][$srv_connect]['maintenance'];

                $this->calculate($price_adr, $maint_adr, 'Сигнализация: Базовый комплект');

                // считаем опции
                $options = array_keys($this->optionsPhys);
                foreach ($options as $option) {
                    if (key_exists($option, $srv)) {
                        $price_adr = $map[$srv_type][$who][$option][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who][$option][$srv_connect]['maintenance'];
                        $this->calculate($price_adr, $maint_adr, $this->optionsPhys[$option]);
                    }
                }
            // для юриков
            } else {
                // считаем код для определения комбинации переключателей
                $code = 0;
                if (key_exists('sec_signaling', $srv)) {
                    $code += 1;
                }
                if (key_exists('fire_signaling', $srv)) {
                    $code += 2;
                }
                if (key_exists('alarm_button', $srv)) {
                    $code += 4;
                }

                switch ($code) {
                    case 1: // если только охранная сигнализация
                        $price_adr = $map[$srv_type][$who]['sec_signaling'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['sec_signaling'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['sec_signaling'];
                        break;
                    case 2: // если только пожарная сигнализация
                        $price_adr = $map[$srv_type][$who]['fire_signaling'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['fire_signaling'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['fire_signaling'];
                        break;
                    case 4: // если только тревожная кнопка
                        $price_adr = $map[$srv_type][$who]['alarm_button'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['alarm_button'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['alarm_button'];
                        break;
                    case 3: // если охранно-пожарная сигнализация
                        $price_adr = $map[$srv_type][$who]['sec_fire_signaling'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['sec_fire_signaling'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['sec_fire_signaling'];
                        break;
                    case 5: // если охранная сигнализация + тревожная кнопка
                        $price_adr = $map[$srv_type][$who]['sec_signaling'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['sec_signaling'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['sec_signaling'];
                        $this->calculate($price_adr, $maint_adr, $specTitle);
                        $price_adr = $map[$srv_type][$who]['alarm_button_opt'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['alarm_button_opt'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['alarm_button_opt'];
                        break;
                    case 6: // если пожарка + тревожная кнопка
                        $price_adr = $map[$srv_type][$who]['fire_signaling'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['fire_signaling'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['fire_signaling'];
                        $this->calculate($price_adr, $maint_adr, $specTitle);
                        $price_adr = $map[$srv_type][$who]['alarm_button'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['alarm_button'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['alarm_button'];
                        break;
                    case 7: // если пожарка + охранка + тревожная кнопка
                        $price_adr = $map[$srv_type][$who]['sec_fire_signaling'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['sec_fire_signaling'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['sec_fire_signaling'];
                        $this->calculate($price_adr, $maint_adr, $specTitle);
                        $price_adr = $map[$srv_type][$who]['alarm_button_opt'][$srv_connect]['price'];
                        $maint_adr = $map[$srv_type][$who]['alarm_button_opt'][$srv_connect]['maintenance'];
                        $specTitle = $this->optionsOrg['alarm_button_opt'];
                        break;
                }
                $this->calculate($price_adr, $maint_adr, $specTitle);
            }
        // видеонаблюдение
        } elseif ($srv_type == 'CCTV') {
            $cctv_place = $srv['CCTV-place-type'] ?? false;
            $price_adr = $map[$srv_type][$cctv_place]['price'];
            $maint_adr = $map[$srv_type][$cctv_place]['maintenance'];
            $this->calculate($price_adr, $maint_adr);
        // физохрана
        } elseif ($srv_type == 'guard') {
            $guard_armed = $srv['guard-armed'] ?? false;
            if ($who == 'nav-phys') {
                $price_adr = $map[$srv_type][$who][$guard_armed];
            } else {
                $guard_mode = $srv['guard-mode'] ?? false;
                $price_adr = $map[$srv_type][$who][$guard_mode][$guard_armed];
            }
            $this->calculate($price_adr);
        // сопровождение грузов
        } elseif ($srv_type == 'cargo_escort') {
            $cargo_armed = $srv['cargo-escort-armed'];
            $price_adr = $map[$srv_type][$cargo_armed];
            $this->calculate($price_adr);
        // GPS мониторинг авто
        } elseif ($srv_type == 'GPS') {
            $price_adr = $map[$srv_type][$who]['price'];
            $maint_adr = $map[$srv_type][$who]['maintenance'];
            $this->calculate($price_adr, $maint_adr);
        }

        return (object) [
            'price' => $this->calcPr,
            'maintenance' => $this->calcMaint,
            'currency' => $price->price_currency,
            'specification' => $this->specification
        ];
    }

    /** Добавляет к стоимости соответствующие цены из прейскуранта и проверяет заполненность цен,
     * формирует спецификацию
     */
    private function calculate($price_adr, $maint_adr = false, $specTitle = '')
    {
        // проверяем, есть ли уже нулевые цены
        if (!$this->noPriceFilled) {
            // получаем цену из объекта price
            $price = $this->price->$price_adr ?? false;
            $maintenance = $this->price->$maint_adr ?? false;

            // если цена есть - суммируем
            if ($price || $price === 0) {
                $this->calcPr += $price;

                // если применима абон плата
                if ($maint_adr && ($maintenance || $maintenance === 0)) {
                    $this->calcMaint += $maintenance;
                }

                // добавляем в спецификацию
                if ($specTitle) $this->specification[] = [$specTitle, $price, $maintenance];
            } 
            
            // если цены на что-то нет, то все обнуляем и формируем признак отсутствия цены
            if ((!$price && $price !== 0) || ($maint_adr && (!$maintenance && $maintenance !== 0))) {
                $this->calcPr = 0;
                $this->calcMaint = 0;
                $this->specification = [];
                $this->noPriceFilled = true;
            }
        }
    }

}
