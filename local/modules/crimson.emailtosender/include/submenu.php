<?php

namespace Crimson\Mail\Sender;

class Submenu {

    CONST ACTION_NAME = 'crimson_message_sender';

    public static $msg = ['type' => "OK", 'text' => ''];

    public static function OnBeforeProlog() {

        if (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true) {
            return;
        }

        if (isset($_REQUEST['action_button']) && !isset($_REQUEST['action'])) {
            $_REQUEST['action'] = $_REQUEST['action_button'];
        }
        if (!isset($_REQUEST['action'])) {
            return;
        }

        global $USER, $APPLICATION;

        if ($_REQUEST['action'] == static::ACTION_NAME && $USER->CanDoOperation('view_other_settings') &&
                $GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/message_admin.php' && check_bitrix_sessid() &&
                $_REQUEST['message_id']
        ) {
            $rsMess = \CEventMessage::GetList($by = "site_id", $order = "desc", ['ID' => (int) $_REQUEST['message_id']]);
            if ($arMess = $rsMess->GetNext()) {
                try {
                    $ex = new \Crimson\Mail\Sender\Creator();
                    static::$msg['posting_id'] = $ex->addFromMailTemplateAndIncludeEvent("\Crimson\Mail\Executors\Base::build", $arMess['EVENT_NAME'], $arMess['SITE_TEMPLATE_ID'], false, false, false);
                    static::$msg['href'] = "/bitrix/admin/sender_letters.php?edit=undefined&ID=" . static::$msg['posting_id'] . "&lang=ru";
                    static::$msg['text'] = "<a target='_blank' href='" . static::$msg['href'] . "'>Открыть созданную рассылку #" . static::$msg['posting_id'] . "</a>";
                } catch (\Exception $exc) {
                    static::$msg['type'] = "ERR";
                    static::$msg['text'] = $exc->getMessage();
                    //static::$msg['text'] .= $exc->getTraceAsString();
                }
            } else {
                static::$msg['text'] = "ERR: {$_REQUEST['message_id']}";
                static::$msg['type'] = "ERR";
            }

            unset($_REQUEST['action']);
        }
    }

    public static function OnAdminListDisplayHandler(\CAdminList &$list) {
//        $listParams = $event->getParameters();
//        pr($listParams); //die();
//        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $listParams);
        if ($GLOBALS['APPLICATION']->GetCurPage() == '/bitrix/admin/message_admin.php') {

            if (strlen(static::$msg['text'])) {
                $message = new \CAdminMessage(array('TYPE' => static::$msg['type'], 'MESSAGE' => static::$msg['text'], 'HTML' => true));
                echo $message->Show();

                if (static::$msg['type'] != "OK") {
                    echo "<script>alert('Ошибка: " . static::$msg['text'] . "')</script>";
                } else {
                    echo "<script>if(confirm('Открыть созданную рассылку #" . static::$msg['posting_id'] . "?')) {location.href='" . static::$msg['href'] . "';}</script>";
                }
            }

            $lAdmin = new \CAdminList($list->table_id, $list->sort);
            foreach ($list->aRows as $id => &$v) {
                array_unshift($v->aActions,
                        [
                            'ICON' => 'move',
                            'TEXT' => "Создать рассылку из шаблона [{$v->arRes['SITE_TEMPLATE_ID']}]",
                            'ACTION' => $lAdmin->ActionDoGroup($v->id, static::ACTION_NAME, '&lang=' . LANGUAGE_ID . '&message_id=' . $v->id)
                        ]
                );
            }
        }
    }

}
