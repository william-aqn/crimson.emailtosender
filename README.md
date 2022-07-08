# crimson.emailtosender
Отправка через email маркетинг обычных почтовых шаблонов в 1С-Битрикс

1. Создаём собственный обработчик по примеру [executors/base.php](/local/modules/crimson.emailtosender/executors/base.php) и добавляем в init 
```
// Сборщик исполнителей для компонента crimson:execute.mail
\Bitrix\Main\EventManager::getInstance()->addEventHandler("crimson", "OnExecutorList",
        ['\Crimson\Mail\Executors\Base', 'loadExecutors']
);
```

2. Приведите email шаблон в приемлемый для отправки вид с помощью компонента include.mail
* Инклудим файл
```
<?EventMessageThemeCompiler::includeComponent(
	"crimson:include.mail",
	"",
	array_merge($arParams,['FILE'=>$_SERVER["DOCUMENT_ROOT"]."/local/templates/email-template/inc.php"])
);?> 
```
* Выводим тэги по условию
```
<?EventMessageThemeCompiler::includeComponent(
	"crimson:include.mail",
	"",
	array_merge($arParams,['array_key_exists'=>'SENDER_CHAIN_CODE','HTML'=>'</td></tr>'])
);?>
```

3. В модуле email-маркетинг создаём рассылку на основе нашего исправленного шаблона

4. Добавляем через видуальный редактор редактора рассылок - компонент execute.mail

# Установка
Скопировать модуль в /local/modules и установить через админку.

# Рекомендации
Их нет. Это костыль.