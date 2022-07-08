<?php

namespace Crimson\Mail\Sender;

// https://tuning-soft.ru/articles/d7-bitrix/d7-sender-creating-api-distribution-in-the-module-email-marketing.html
// \bitrix\components\bitrix\sender.letter.edit\class.php

/**
 * Создаватель выпусков
 */
class Creator {

    const CREATOR_USER_ID = 1;

    private $letter;

    public function __construct() {
        \Bitrix\Main\Loader::includeModule('sender');
    }

    /**
     * Создать рассылку с произвольным HTML кодом
     * @param string $title Заголовок письма
     * @param string $html Целиком HTML (c html/body)
     * @param string $from Отправитель (email)
     * @param array $segment Сегмент [1]
     * @param boolean $run Запустить рассылку
     * @return int ID рассылки
     */
    public function addHtml($title, $html, $from, $segment, $run = false) {
        $params = [
            'TITLE' => $title,
            'MESSAGE' => $html,
            'FROM' => $from,
            'TEMPLATE_TYPE' => 'BASE',
            'TEMPLATE_ID' => 'empty',
            'SEGMENT' => $segment,
            'RUN' => $run,
        ];
        return $this->add($params);
    }

    /**
     * Создать рассылку на основе email шаблона и шаблона email события
     * @param string $title Заголовок письма
     * @param string $executorClass \Crimson\Mail\Executors\Base::build
     * @param string $templateEventType Код email события
     * @param string $templateCode Код email шаблона
     * @param string $from Отправитель (email)
     * @param array $segment Сегмент
     * @param boolean $run Запустить рассылку
     * @return int ID рассылки
     */
    public function addFromMailTemplateAndIncludeEvent($title, $executorClass, $templateEventType, $templateCode, $from, $segment, $run = false) {
        // 
        $executorClass = addslashes($executorClass);
        // TODO: проверка на существующие Ececutors
        $htmlBodyContent = '<div data-bx-block-editor-block-type="component">
<?EventMessageThemeCompiler::includeComponent(
	"crimson:execute.mail",
	"",
	Array(
		"TEMPLATE_EXECUTE_CLASS" => "' . $executorClass . '",
		"TEMPLATE_TYPE" => "' . $templateEventType . '",
		"USER_ID" => "{#USER_ID#}"
	)
);?>
</div>';
        return $this->addFromMailTemplate($title, $htmlBodyContent, $templateCode, $from, $segment, $run);
    }

    /**
     * Создать рассылку с произвольным html на основе email шаблона
     * @param string $title Заголовок письма
     * @param string $htmlBodyContent HTML тела (без head/body)
     * @param string $templateCode Код email шаблона
     * @param string $from Отправитель (email)
     * @param array $segment Сегмент
     * @param boolean $run Запустить рассылку
     * @return int ID рассылки
     */
    public function addFromMailTemplate($title, $htmlBodyContent, $templateCode, $from, $segment, $run = false) {
        $params = [
            'TITLE' => $title,
            'MESSAGE' => '<!--START BX_BLOCK_EDITOR_EDITABLE_SECTION/BLOCKS/body/-->'
            . '' . $htmlBodyContent . ''
            . '<!--END BX_BLOCK_EDITOR_EDITABLE_SECTION/BLOCKS/body/-->', // \bitrix\modules\fileman\lib\block\content\engine.php
            'FROM' => $from,
            'TEMPLATE_TYPE' => 'SITE_TMPL',
            'TEMPLATE_ID' => $templateCode,
            'SEGMENT' => $segment,
            'RUN' => $run,
        ];
        return $this->add($params);
    }

    /**
     * Создать рассылку
     * @param array $params
     * @return int ID рассылки
     * @throws \Exception
     */
    function add($params = []) {
        // Prepare letter
        $this->letter = \Bitrix\Sender\Entity\Letter::createInstanceById(0, [\Bitrix\Sender\Message\iBase::CODE_MAIL]); //array('mail', 'sms')
        if (!$this->letter || !is_object($this->letter)) {
            throw new \Exception(\Bitrix\Sender\Security\AccessChecker::ERR_CODE_NOT_FOUND);
        }
        if (!$params['TEMPLATE_TYPE']) {
            $params['TEMPLATE_TYPE'] = 'BASE'; // BASE для HTML // SITE_TMPL
        }
        if (!$params['TEMPLATE_ID']) {
            $params['TEMPLATE_ID'] = 'empty'; // empty для HTML // proxy-seller-email-send
        }
        if (!$params['TITLE']) {
            $params['TITLE'] = 'Рассылка от ' . date("d.m.Y H:i:s");
        }
        if (!$params['MESSAGE']) {
            $params['MESSAGE'] = '<html></html>';
        }
        if (!$params['FROM']) {
            $params['FROM'] = \Bitrix\Main\Config\Option::get('main', 'email_from');
        }
        if (!$params['SEGMENT']) {
            $segments = \Bitrix\Sender\Entity\Segment::getDefaultIds();
            $params['SEGMENT'] = $segments ? $segments : array(1);
        }

        $data = [
            'TITLE' => $params['TITLE'],
            'SEGMENTS_INCLUDE' => $segments,
            //'SEGMENTS_EXCLUDE' => $this->preparePostSegments(false),
            'TEMPLATE_TYPE' => $params['TEMPLATE_TYPE'],
            'TEMPLATE_ID' => $params['TEMPLATE_ID'],
            'IS_TRIGGER' => 'N',
            'MESSAGE_CODE' => \Bitrix\Sender\Message\iBase::CODE_MAIL, // \bitrix\modules\sender\lib\message\ibase.php
            'UPDATED_BY' => static::CREATOR_USER_ID
        ];

        if (!$this->letter->getId()) {
            //$data['CAMPAIGN_ID'] = $this->arParams['CAMPAIGN_ID'] ?: Entity\Campaign::getDefaultId(SITE_ID);
            $data['CREATED_BY'] = static::CREATOR_USER_ID;
        }
        $this->letter->mergeData($data);

        // Add message

        $subject = $params['TITLE'];
        $linkParams = \CUtil::translit($subject, 'ru');

        $message = $this->letter->getMessage();
        $configuration = $message->getConfiguration();

        $cfg = array(
            'SUBJECT' => $subject,
            'MESSAGE' => $params['MESSAGE'],
            'EMAIL_FROM' => $params['FROM'],
            'PRIORITY' => '',
            'TRACK_MAIL' => 'Y',
            'LINK_PARAMS' => 'utm_source=newsletter&utm_medium=mail&utm_campaign=' . $linkParams,
            'ATTACHMENT' => '',
            'TEMPLATE_TYPE' => $params['TEMPLATE_TYPE'],
            'TEMPLATE_ID' => $params['TEMPLATE_ID'],
        );
        pr($cfg);
        foreach ($configuration->getOptions() as $option) {
            $key = $option->getCode();

            if (array_key_exists($key, $cfg)) {
                $value = $cfg[$key];
            }

            /* switch($code) {
              case 'MESSAGE':
              $value = str_replace('#MESSAGE#', $message, $baseConfig->getOption($code)->getValue());
              break;
              case 'SUBJECT':
              $value = $arItem['~NAME'];
              break;
              case 'EMAIL_FROM':
              $value = $baseConfig->getOption($code)->getValue();
              break;
              default:
              $value = $baseConfig->getOption($code)->getValue();
              } */

            $option->setValue($value);
        }

        /*
          //Message field example #1
          $option = $configuration->getOptionByType(Bitrix\Sender\Message\ConfigurationOption::TYPE_MAIL_EDITOR);
          if($option) {
          $message = str_replace('#MESSAGE#', $message, $option->getValue());
          $option->setValue($message);
          }

          //Template field example #2
          $letter->getMessage()->getConfiguration()->set('TEMPLATE_TYPE', $params['TEMPLATE_TYPE']);
          $letter->getMessage()->getConfiguration()->set('TEMPLATE_ID', $params['TEMPLATE_ID']);
         */

        // Save letter
        $result = $configuration->checkOptions();
        if ($result->isSuccess()) {

            // Сохраняет настройки полей выпуска в таблицу b_sender_message_field
            // \bitrix\modules\sender\lib\integration\sender\mail\messagemail.php
            $resultConfig = $message->saveConfiguration($configuration);
            if (!$resultConfig->isSuccess()) {
                throw new \Exception(implode(",", $resultConfig->getErrorMessages()));
            }

            $this->letter->set('MESSAGE_ID', $configuration->getId());

            if ($this->letter->save()) {
                //Send mail
                if ($params['RUN'] === true || $params['RUN'] === 'Y') {
                    //$this->letter->getState()->send();
                }
                return $this->letter->getId();
            }
        } else {
            throw new \Exception(implode(",", $result->getErrorMessages()));
        }

        if ($this->letter->hasErrors()) {
            throw new \Exception(implode(",", $this->letter->getErrorMessages()));
        }
    }

}
