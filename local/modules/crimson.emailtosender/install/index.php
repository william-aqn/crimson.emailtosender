<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (class_exists("crimson_emailtosender"))
    return;

class crimson_emailtosender extends CModule {

    var $MODULE_ID = 'crimson.emailtosender';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;
    public $MODULE_GROUP_RIGHTS = 'N';
    public $NEED_MAIN_VERSION = '';
    public $NEED_MODULES = array();

    public function __construct() {

        $arModuleVersion = array();

        $path = str_replace('\\', '/', __FILE__);
        $path = substr($path, 0, strlen($path) - strlen('/index.php'));
        include($path . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage("CRIMSON_EMAIL2SENDER_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("CRIMSON_EMAIL2SENDER_MODULE_DISCRIPTION");

        $this->PARTNER_NAME = Loc::getMessage("CRIMSON_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("CRIMSON_PARTNER_URI");
        $this->NEED_MODULES = ['sender']; // Нужен модуль рассылок
    }

    public function DoInstall() {
        if (is_array($this->NEED_MODULES) && !empty($this->NEED_MODULES)) {
            foreach ($this->NEED_MODULES as $module) {
                if (!IsModuleInstalled($module)) {
                    $this->ShowForm('ERROR', GetMessage('CRIMSON_NEED_MODULES', array('#MODULE#' => $module)));
                }
            }
        }
        $this->InstallFiles();
        \Bitrix\Main\EventManager::getInstance()->registerEventHandler('crimson', 'OnExecutorList', $this->MODULE_ID, '\Crimson\Mail\Executors\Base', 'loadExecutors');
        RegisterModule($this->MODULE_ID);
        $this->ShowForm('OK', GetMessage('MOD_INST_OK'));
    }

    public function DoUninstall() {
        $this->UnInstallFiles();
        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('crimson', 'OnExecutorList', $this->MODULE_ID, '\Crimson\Mail\Executors\Base', 'loadExecutors');
        UnRegisterModuleDependences('crimson', 'OnExecutorList', $this->MODULE_ID, '\Crimson\Mail\Executors\Base', 'loadExecutors');
        UnRegisterModule($this->MODULE_ID);
        $this->ShowForm('OK', GetMessage('MOD_UNINST_OK'));
    }

    public function InstallFiles($arParams = array()) {
        if (is_dir($p = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $this->MODULE_ID . '/install/components')) {
            if ($dir = opendir($p)) {
                while (false !== $item = readdir($dir)) {
                    if ($item == '..' || $item == '.')
                        continue;
                    CopyDirFiles($p . '/' . $item, $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/' . $item, $ReWrite = True, $Recursive = True);
                }
                closedir($dir);
            }
        }
        return true;
    }

    public function UnInstallFiles() {
        if (is_dir($p = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $this->MODULE_ID . '/install/components')) {
            if ($dir = opendir($p)) {
                while (false !== $item = readdir($dir)) {
                    if ($item == '..' || $item == '.' || !is_dir($p0 = $p . '/' . $item))
                        continue;

                    $dir0 = opendir($p0);
                    while (false !== $item0 = readdir($dir0)) {
                        if ($item0 == '..' || $item0 == '.')
                            continue;
                        DeleteDirFilesEx('/bitrix/components/' . $item . '/' . $item0);
                    }
                    closedir($dir0);
                }
                closedir($dir);
            }
        }
        return true;
    }

    private function ShowForm($type, $message, $buttonName = '') {
        // Костыль ёпт
        $keys = array_keys($GLOBALS);
        for ($i = 0, $intCount = count($keys); $i < $intCount; $i++) {
            if ($keys[$i] != 'i' && $keys[$i] != 'GLOBALS' && $keys[$i] != 'strTitle' && $keys[$i] != 'filepath') {
                global ${$keys[$i]};
            }
        }

        $APPLICATION->SetTitle($this->MODULE_NAME);
        include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
        echo CAdminMessage::ShowMessage(array('MESSAGE' => $message, 'TYPE' => $type));
        ?>
        <form action="<?= $APPLICATION->GetCurPage() ?>" method="get">
            <p>
                <input type="hidden" name="lang" value="<? echo LANGUAGE_ID; ?>" />
                <input type="submit" value="<?= strlen($buttonName) ? $buttonName : GetMessage('MOD_BACK') ?>" />
            </p>
        </form>
        <?
        include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
        die();
    }

}
