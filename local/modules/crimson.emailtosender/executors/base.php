<?php
namespace Crimson\Mail\Executors;

class Base {

    /**
     * Даём знать компоненту что мы можем что то делать
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function loadExecutors(\Bitrix\Main\Event $event) {
        $connectorParams = $event->getParameters();
        $connectorParams['EXECUTORS'][] = __CLASS__ . "::build"; // Название метода
        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $connectorParams);
    }
    
    /**
     * Готовим перменные для шаблона
     * @param integer $userId
     * @return array
     */
    static function build($userId){
        $ret = ['USER_ID'=>$userId];
        return $ret;
    }

}
