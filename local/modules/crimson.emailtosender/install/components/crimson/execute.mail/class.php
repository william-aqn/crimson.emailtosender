<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class CrimsonExecuteMail extends CBitrixComponent {

    private $lid;
    private $template;
    private $variables;

    public function onPrepareComponentParams($arParams) {
        $result = [
            "USER_ID" => (int) $arParams["USER_ID"],
            "TEMPLATE_TYPE" => $arParams["TEMPLATE_TYPE"],
            "TEMPLATE_EXECUTE_CLASS" => $arParams["TEMPLATE_EXECUTE_CLASS"],
        ];
        return $result;
    }

    /**
     * Находим LID пользователя
     * @return boolean
     */
    private function initUserLid() {
        $res = \Bitrix\Main\UserTable::getList(Array(
                    "select" => ["LID"],
                    "filter" => ['=ID' => $this->arParams["USER_ID"]],
        ));
        if ($arRes = $res->fetch()) {
            $this->lid = $arRes['LID'];
            return $this->lid;
        } else {
            $res = \Bitrix\Main\SiteTable::getList([
                        "select" => ["LID"],
                        "filter" => ['=DEF' => 'Y']
            ]);
            if ($arRes = $res->fetch()) {
                $this->lid = $arRes['LID'];
                return $this->lid;
            }
        }
        return false;
    }

    /**
     * Находим email шаблон
     * @return boolean
     */
    private function initTemplate() {
        if (!$this->initUserLid()) {
            return false;
        }
        $rsMess = \CEventMessage::GetList($by = "site_id", $order = "desc", ['=TYPE_ID' => $this->arParams["TEMPLATE_TYPE"], '=LID' => $this->lid]);
        if ($arMess = $rsMess->GetNext()) {
            $this->template = html_entity_decode($arMess['MESSAGE']);
            return $this->template;
        }
        return false;
    }

    /**
     * Запускаем обработчик шаблона
     * @return boolean
     */
    private function initBuilder() {
        if (!$this->initTemplate()) {
            return false;
        }
        if ($this->variables = call_user_func($this->arParams["TEMPLATE_EXECUTE_CLASS"], $this->arParams["USER_ID"])) {
            return $this->variables;
        }
        return false;
    }
    
    /**
     * Заполняем шаблон
     * @return boolean
     */
    private function build() {
        if (!$this->initBuilder()) {
            return false;
        }
        $ret = $this->template;
        foreach ($this->variables as $key => $value) {
            $ret = str_replace("#$key#", $value, $ret);
        }
        return $ret;
    }

    /**
     * Выводим данные
     * @return boolean
     */
    public function executeComponent() {
        if ($this->arParams["USER_ID"] <= 0 || !$this->arParams["TEMPLATE_TYPE"] || !$this->arParams["TEMPLATE_EXECUTE_CLASS"]) {
            return false;
        }
        if ($result = $this->build()) {
            echo $result;
            //$this->includeComponentTemplate();
            return true;
        }
        return false;

    }

}
