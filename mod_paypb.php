<?php
/**
 * здесь описание и комментарии
 */
defined('_JEXEC') or die;
if (!defined( 'DS' )) {
    define( 'DS', DIRECTORY_SEPARATOR );
}

// NBelyanskiy
use Joomla\Registry\Registry;
$captchaEnabled = false;
$captchaPublicKey = "";

$pluginCapthaEnable = JPluginHelper::getPlugin('captcha','recaptcha_invisible');
if(!empty($pluginCapthaEnable)){
    $params = new Registry($pluginCapthaEnable->params);
    $captchaPublicKey = $params->public_key;
    $captchaEnabled = true;
}


$doc =JFactory::getDocument();
$doc->addStyleSheet(JUri::root(TRUE)."/modules/mod_paypb/css/style.css");
//$doc->addScript("https://code.jquery.com/jquery-3.4.1.min.js",'text/javascript');

$doc->addScript(JUri::root(TRUE)."/modules/mod_paypb/js/handlebars.runtime-v4.1.2.js",'text/javascript');
$doc->addScript(JUri::root(TRUE)."/modules/mod_paypb/js/handlebars-v4.1.2.js",'text/javascript');
$doc->addScript(JUri::root(TRUE)."/modules/mod_paypb/js/script.js",'text/javascript');


require_once(JPATH_SITE.DS."components".DS."com_ttfsp".DS."vendor".DS.'autoload.php');
require_once(JPATH_SITE.DS."modules".DS."mod_paypb".DS."LiqPay.php");
//$doc->addStyleSheet( JUri::root(TRUE)."/modules/mod_news/css/multimedia_perspective_carousel.css", [], ['rel'=>'preload','as'=>'style', 'onload'=>'this.rel=\'stylesheet\''] );

//$doc->addScript("https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js");
//$doc->addScript(JUri::root(TRUE)."/modules/mod_news/js/jquery.ui.touch-punch.min.js");
//$doc->addScript(JUri::root(TRUE)."/modules/mod_news/js/multimedia_perspective_carousel.js");
// подключаем наш хелпер
require_once __DIR__ . '/helper.php';

$cache = JFactory::getCache('com_content');
$cache->clean();
$cache = JFactory::getCache('mod_paypb');
$cache->clean();
//вызываем метод getNews(), который находится в хелпере
//(извлекает из базы данных нужную нам информацию



$postData = JFactory::getApplication()->input;


$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');
//подключаем html-шаблон для вывода содержания модуля (шаблон default).
require JModuleHelper::getLayoutPath('mod_paypb', $params->get('layout', 'default'));