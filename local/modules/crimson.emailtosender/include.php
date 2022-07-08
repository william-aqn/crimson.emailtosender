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

}

\Bitrix\Main\Loader::registerAutoLoadClasses(null, array(
    '\Crimson\Mail\Executors\Base' => '/local/modules/crimson.emailtosender/executors/base.php', // Пример
));
