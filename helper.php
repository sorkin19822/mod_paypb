<?php
 
defined('_JEXEC') or die;

class modPaypbHelper
{

	public static function getForm()
	{
		jimport( 'joomla.application.module.helper' ); // подключаем нужный класс, один раз на странице, перед первым выводом
		$module = JModuleHelper::getModule('mod_paypb'); // получаем объект модуля, mod_module - имя модуля в папке modules
		$param = json_decode($module->params); // декодирует JSON с параметрами модуля
       $form = modPaypbHelper::generateForm(time(),1);
		return $form;
	}


    public static function getOrderId(){
        $input = JFactory::getApplication()->input;
        $orderId = $input->get('orId', null, "ALNUM");
        $num_length = strlen((string)$orderId);
        if($num_length == 8) {
            return "value = $orderId";
        } else {
            // Fail
        }
    }

    public static function getSecCode(){
        $input = JFactory::getApplication()->input;
        return $seccode = $input->get('seccode', null, "ALNUM");
    }



}