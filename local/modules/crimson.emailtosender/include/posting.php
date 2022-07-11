<?php

namespace Crimson\Mail\Sender;

class Posting {

    /**
     * Задействуем многоязычность у шаблона для конкретного пользователя
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function OnBeforePostingSendRecipient(\Bitrix\Main\Event $event) {
        $parameters = $event->getParameters()[0];

        if (!$parameters['FIELDS']['USER_ID']) {
            $info = \CrimsonEmailToSenderHelper::getUserLidAndLanguageId(0, $parameters['FIELDS']['EMAIL_TO']);
            $parameters['FIELDS']['USER_ID'] = $info['ID'];
            $parameters['FIELDS']['SITE_ID'] = $info['LID']; // Задействуем многоязычность у шаблона
            $parameters['FIELDS']['LANGUAGE_ID'] = $info['LANGUAGE_ID']; // Задействуем многоязычность у шаблона
        }

        if (!$parameters['FIELDS']['SITE_ID']) {
            $info = \CrimsonEmailToSenderHelper::getUserLidAndLanguageId(0, $parameters['FIELDS']['EMAIL_TO']);
            $parameters['FIELDS']['SITE_ID'] = $info['LID']; // Задействуем многоязычность у шаблона
            $parameters['FIELDS']['LANGUAGE_ID'] = $info['LANGUAGE_ID']; // Задействуем многоязычность у шаблона
        }

        $parameters['TRACK_CLICK']['FIELDS']['LANG'] = $parameters['FIELDS']['LANGUAGE_ID'];

        a2l($event, 'OnBeforePostingSendRecipient', 'before_posting_event');
        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $parameters);
    }

    /**
     * Подменяем языковую фразу в заголовке
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function onBeforeMailSend(\Bitrix\Main\Event $event) {
        $mailParams = $event->getParameter(0);
        if ($mailParams['TRACK_READ']['FIELDS']['LANG'] && stripos($mailParams['SUBJECT'], $mailParams['TRACK_READ']['FIELDS']['LANG']) !== false) {
            $mailParams['SUBJECT'] = static::lang($mailParams['SUBJECT'], $mailParams['TRACK_READ']['FIELDS']['LANG']);
        }
        a2l($event, 'onBeforeMailSend', 'email_send_ex');

        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $mailParams);
    }

    public static function lang($subject, $lang) {
        $ret = explode("|", $subject);
        array_walk($ret, 'trim');
        foreach ($ret as $localeTitle) {
            // TODO: именно первые символы
            if (stripos($localeTitle, $lang . ":") !== false) {
                return trim(str_replace($lang . ":", "", $localeTitle));
            }
        }
    }

}
