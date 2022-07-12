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
        global $USER;
        $info = \CrimsonEmailToSenderHelper::getUserLidAndLanguageId($USER->GetID());
        $editorParams['SITE'] = $info['LID'] ?? $editorParams['SITE'];
        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $editorParams);
    }

}
