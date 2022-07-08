<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class CrimsonIncludeMail extends CBitrixComponent {

    public function executeComponent() {
        // Да
        if ($this->arParams["FILE"] && file_exists($this->arParams["FILE"])) {
            $arParams=$this->arParams;
            include $this->arParams["FILE"];
        }
        // Проверяем наличие ключа в параметрах
        if ($this->arParams["array_key_exists"]) {
            if (array_key_exists($this->arParams["array_key_exists"], $this->arParams)) {
                echo html_entity_decode($this->arParams['HTML']);
            }
        }
        // Если инициатор - модуль email рассылки
        if ($this->arParams["IS_SENDER"]) {
            if (array_key_exists("SENDER_CHAIN_CODE", $this->arParams)) {
                echo html_entity_decode($this->arParams['HTML']);
            }
        }
    }

}
