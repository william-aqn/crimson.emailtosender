<?php

namespace Crimson\Mail\Sender;

class Editor {

    /**
     * Задействуем многоязычность у редактора
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function OnBeforeBlockEditorMailPreview(\Bitrix\Main\Event $event) {
        $editorParams = $event->getParameters();
        $editorParams['SITE'] = static::getLidFromCurrentUser() ?? $editorParams['SITE'];
        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $editorParams);
    }

    static function getLidFromCurrentUser() {
        global $USER;
        $res = \Bitrix\Main\UserTable::getList(Array(
                    "select" => ["LID"],
                    "filter" => ['=ID' => $USER->GetID()],
        ));
        if ($arRes = $res->fetch()) {
            return $arRes['LID'];
        }
    }

}
