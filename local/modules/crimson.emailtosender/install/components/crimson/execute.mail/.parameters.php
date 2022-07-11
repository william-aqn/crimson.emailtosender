<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();


$emailTypes = [];
$rsET = \CEventType::GetList(['LID' => SITE_ID], ['NAME' => 'ASC']);
while ($arET = $rsET->Fetch()) {
    $emailTypes[$arET['EVENT_NAME']] = "{$arET['NAME']} [{$arET['EVENT_NAME']}]";
}

\Bitrix\Main\Loader::includeModule('crimson.emailtosender');
$executors = \CrimsonEmailToSenderHelper::toParameters();

$arComponentParameters = [
    "PARAMETERS" => [
        "USER_ID" => [
            "NAME" => "ID пользователя",
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => [
                "{#USER_ID#}" => "={#USER_ID#}",
                "{#ORDER_USER_ID#}" => "={#ORDER_USER_ID#}",
                "{#ID#}" => "={#ID#}",
            ],
            "ADDITIONAL_VALUES" => "Y",
            "DEFAULT" => [
                "{#USER_ID#}" => "{#USER_ID#}"
            ],
            #"COLS" => 25,
            "PARENT" => "BASE",
        ],
        "EVENT_NAME" => [
            "NAME" => "Тип события",
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => $emailTypes,
            "ADDITIONAL_VALUES" => "Y",
            "PARENT" => "BASE",
        ],
        "TEMPLATE_EXECUTE_CLASS" => [
            "NAME" => "Функция для заполнения переменными шаблона события",
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => $executors,
            "ADDITIONAL_VALUES" => "Y",
            "PARENT" => "BASE",
        ],
    ]
];

