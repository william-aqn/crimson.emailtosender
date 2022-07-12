<?php

namespace Crimson\Mail\Sender;

class Posting {
    /*
      Есть в наличии:
      OnBeforePostingSendRecipient - перед отправкой
      OnPostingSendRecipient - после подготовки шаблона, но перед выполнением компонентов
      OnPostingSendRecipientEmail - сформировано конечное письмо, перед отправкой
      OnAfterPostingSendRecipient - после отправки и после смены статусов
     */

    /**
     * Тут можно BODY поменять
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
//    public static function OnPostingSendRecipientEmail(\Bitrix\Main\Event $event) {
//        $parameters = $event->getParameters()[0];
//        // a2l($event, 'OnPostingSendRecipientEmail', 'before_posting_event_ex');
//        $mod = [];
//        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $mod);
//    }

    /**
     * Задействуем многоязычность у шаблона для конкретного пользователя
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function OnBeforePostingSendRecipient(\Bitrix\Main\Event $event) {
        $parameters = $event->getParameters()[0];
        $mod = [];
        if (!$parameters['FIELDS']['SITE_ID'] && !$parameters['FIELDS']['LANGUAGE_ID']) {
            $mod = [
                'FIELDS' => $parameters['FIELDS'],
                'TRACK_CLICK' => $parameters['TRACK_CLICK'],
            ];
            $info = \CrimsonEmailToSenderHelper::getUserLidAndLanguageId(0, $parameters['FIELDS']['EMAIL_TO']);
            $mod['FIELDS']['SITE_ID'] = $info['LID']; // Задействуем многоязычность у шаблона
            $mod['FIELDS']['LANGUAGE_ID'] = $info['LANGUAGE_ID']; // Задействуем многоязычность у шаблона
            // Дополняем недостающую информацию
            if (!$parameters['FIELDS']['USER_ID']) {
                $mod['FIELDS']['USER_ID'] = $info['ID'];
            }

            // Используем TRACK_CLICK для наших дел
            // Пробрасываем в onBeforeMailSend
            $mod['TRACK_CLICK']['FIELDS']['LANG'] = $mod['FIELDS']['LANGUAGE_ID'];
        }

        // a2l($event, 'OnBeforePostingSendRecipient', 'before_posting_event_ex');
        // a2l($mod, 'MOD - OnBeforePostingSendRecipient', 'before_posting_event_ex');
        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $mod);
    }

    /**
     * Подменяем языковую фразу в заголовке
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function onBeforeMailSend(\Bitrix\Main\Event $event) {
        $mailParams = $event->getParameter(0);
        $mod = [];
        $lang = $mailParams['TRACK_CLICK']['FIELDS']['LANG'];

        // Если тестовая отправка через редактор
        // TODO: Опционально сделать для всех писем
        if ($mailParams['TRACK_CLICK']['MODULE_ID'] === 'sender' && $mailParams['TRACK_CLICK']['FIELDS']['RECIPIENT_ID'] === 0) {
            $info = \CrimsonEmailToSenderHelper::getUserLidAndLanguageId(0, $mailParams['TO']);
            $lang = $info['LANGUAGE_ID'];
        }

        // Заменяем строку
        if ($lang && stripos($mailParams['SUBJECT'], $lang) !== false) {
            $mod['SUBJECT'] = static::lang($mailParams['SUBJECT'], $lang);
        }

        //a2l($mailParams, 'onBeforeMailSend', 'email_send_ex');
        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $mod);
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
