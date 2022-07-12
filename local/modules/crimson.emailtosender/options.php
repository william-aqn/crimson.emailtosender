<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
\Bitrix\Main\Loader::includeModule(basename(__DIR__));

/**
 * https://dev.1c-bitrix.ru/community/webdev/user/203730/blog/13249/
 */
class CrimsonEmailToSenderOptions {

    private $module_id;

    public function __construct() {
        $this->module_id = \basename(__DIR__);

        global $APPLICATION;
        $AUTH_RIGHT = $APPLICATION->GetGroupRight($this->module_id);

        if ($AUTH_RIGHT <= "D") {
            $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
        }

        $aTabs = [
            [
                "DIV" => "edit_all",
                "TAB" => "Общие настройки",
                "OPTIONS" => [
                   
                    [
                        'REPLACE_ALL_TITLE_FROM_EMAIL', // Ключ
                        'Локализировать заголовки только из модуля рассылок (sender)', // Название поля
                        'Y', // По умолчанию
                        [
                            'checkbox',
                        ]
                    ],
                    [
                        'SET_SITE_ID_AFTER_LOGIN',
                        'Обновлять поле LID (привязка к сайту) после авторизации', 
                        'N',
                        [
                            'checkbox',
                        ]
                    ],
                     
                    ['note' => 'LANGUAGE_ID - поле не обязательное, может вызвать расхождение по LID'],
                    [
                        'SET_LANGUAGE_ID_AFTER_LOGIN',
                        'Обновлять поле LANGUAGE_ID (привязка к языку) после авторизации',
                        'N', // По умолчанию
                        [
                            'checkbox'
                        ]
                    ],
                ]
            ]
        ];

        // Сохраняем настройки
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && strlen($_REQUEST['save']) > 0 && $AUTH_RIGHT == "W" && check_bitrix_sessid()) {
            foreach ($aTabs as $aTab) {
                \__AdmSettingsSaveOptions($this->module_id, $aTab['OPTIONS']);
            }
            LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID . '&mid_menu=1&mid=' . urlencode($this->module_id) .
                    '&tabControl_active_tab=' . urlencode($_REQUEST['tabControl_active_tab']) . '&sid=' . urlencode(SITE_ID));
        }

        // Показываем форму
        $tabControl = new \CAdminTabControl('tabControl', $aTabs);
        ?><form method='post' action='' name='bootstrap'>
        <?
            $tabControl->Begin();
            foreach ($aTabs as $aTab) {
                $tabControl->BeginNextTab();
                \__AdmSettingsDrawList($this->module_id, $aTab['OPTIONS']);
            }
            $tabControl->Buttons(array('btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false));
            ?>
            <?= \bitrix_sessid_post(); ?>
            <? $tabControl->End(); ?>
        </form><?
    }

}

new \CrimsonEmailToSenderOptions();
