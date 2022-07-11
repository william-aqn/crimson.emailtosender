<?php

//use Bitrix\Main\Localization\Loc;
//
//Loc::loadMessages(__FILE__);

class CrimsonEmailToSenderHelper {

    const EVENT_NAME = "OnExecutorList";

    /**
     * Сборщик исполнителей
     * @param type $type
     * @return boolean
     */
    static function get($type = 'mail') {
        $event = new \Bitrix\Main\Event('crimson', 'OnExecutorList', ['type' => $type]);
        $event->send();
        $executors = [];
        foreach ($event->getResults() as $eventResult) {
            if (
                    $eventResult->getType() === \Bitrix\Main\EventResult::ERROR ||
                    ($eventResult->getParameters() && count($eventResult->getParameters()['EXECUTORS']) == 0)
            ) {
                return false;
            }
            $executors = array_merge($executors, $eventResult->getParameters()['EXECUTORS']);
        }

        return $executors;
    }

    /**
     * Для .parameters.php компонента crimson:execute.mail
     * @param type $type
     * @return type
     */
    static function toParameters($type = 'mail') {
        $exe = static::get($type);
        return array_combine($exe, $exe);
    }

    /**
     * Получить LID и LANGUAGE_ID по $userId и/или $email
     * @param integer $userId
     * @param string $email
     * @return array
     */
    static function getUserLidAndLanguageId($userId, $email = "") {
        if (!$userId && !$email) {
            return static::getDefaultLidAndLang();
        }
        $filter = ['!LID' => false];

        if ($userId) {
            $filter['=ID'] = $userId;
        }
        if ($email) {
            $filter['=EMAIL'] = $email;
        }
        $res = \Bitrix\Main\UserTable::getList(Array(
                    "select" => ["ID", "LID", 'LANGUAGE_ID', 'SITE_LID' => 'site_ref.LID', 'SITE_LANGUAGE_ID' => 'site_ref.LANGUAGE_ID'],
                    "filter" => $filter,
                    'runtime' => [
                        'site_ref' => [
                            'data_type' => '\Bitrix\Main\SiteTable',
                            'reference' => [
                                '=this.LID' => 'ref.LID',
                            ],
                            'join_type' => 'left'
                        ]
                    ]
        ));
        if ($arRes = $res->fetch()) {
            if (!$arRes['LANGUAGE_ID']) {
                $arRes['LANGUAGE_ID'] = $arRes['SITE_LANGUAGE_ID'];
            }
            return $arRes;
        } else {
            return static::getDefaultLidAndLang();
        }
        return false;
    }

    static function getDefaultLidAndLang() {
        $res = \Bitrix\Main\SiteTable::getList([
                    "select" => ["LID", 'LANGUAGE_ID'],
                    "filter" => ['=DEF' => 'Y']
        ]);
        if ($arRes = $res->fetch()) {
            return $arRes;
        } else {
            return false;
        }
    }

}

\Bitrix\Main\Loader::registerAutoLoadClasses(null, array(
    '\Crimson\Mail\Executors\Base' => '/local/modules/crimson.emailtosender/include/executors/base.php', // Пример
    '\Crimson\Mail\Sender\Creator' => '/local/modules/crimson.emailtosender/include/creator.php', // Создаватель выпусков
    '\Crimson\Mail\Sender\Editor' => '/local/modules/crimson.emailtosender/include/editor.php', // Визуальный редактор
    '\Crimson\Mail\Sender\Posting' => '/local/modules/crimson.emailtosender/include/posting.php', // Перехватываем отправку выпуска
    '\Crimson\Mail\Sender\Submenu' => '/local/modules/crimson.emailtosender/include/submenu.php', // Ручная генерация выпуска
));
