<?php
define( '_JEXEC', 1 );
if (!defined( 'DS' )) {
    define( 'DS', DIRECTORY_SEPARATOR );
}


if ( file_exists( $_SERVER['DOCUMENT_ROOT'].'/defines.php' ) ) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/defines.php';
}
if ( !defined( '_JDEFINES' ) ) {
    define( 'JPATH_BASE', $_SERVER['DOCUMENT_ROOT'] );
    require_once JPATH_BASE . '/includes/defines.php';
}
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_BASE .DS."modules".DS."mod_paypb".DS."LiqPay.php";
require_once JPATH_BASE .DS."modules".DS."mod_paypb".DS."helperFunc.php";
require_once(JPATH_SITE.DS."components".DS."com_ttfsp".DS."vendor".DS.'autoload.php');

if ( file_exists( JPATH_BASE .DS."modules".DS."mod_paypb".DS."config.php") ) {
    require_once JPATH_BASE .DS."modules".DS."mod_paypb".DS."config.php";
}





function mailTemplate($invoiceData, $status){
        $template = <<<EOT
<ul style="    
    list-style: none;
    max-width: 450px;
    padding: 10px;">
    <li><h2 style="    background: #FF9400; text-align: center; color: white;">Замовлення <b style="">№ $invoiceData->invoice</b><br> на суму $invoiceData->summ грн. підтверджено</h2></li>
    <li><p>Доброго дня, $invoiceData->fio! Якщо у Вас з’явилися питання або виникли труднощі з оплатою - звертайтеся у Контакт-центр за телефоном: <br><span><a href="tel:0954087707">(095) 408 77 07</a></span></p></li>
    <li>Сайт <a href="https://dobro-clinic.com" target="_blank">dobro-clinic.com</a></li>
</ul>
<h1>$status</h1>
EOT;
        return $template;
    }



function mailInvoice($email,$templateMail){
    JLog::addLogger(array('text_file' => 'mod_paypb-responsemail-'.date ( 'Y-F' ).'.php'));
    $mailer = JFactory::getMailer();
    $config = JFactory::getConfig();
    $sender = array(
        $config->get( 'mailfrom' ),
        $config->get( 'fromname' )
    );

    $mailer->setSender($sender);
    $mailer->addRecipient($email);
    $mailer->setSubject('Сплата медичних послуг');
    $mailer->isHtml(true);
    $mailer->Encoding = 'base64';
    $mailer->setBody($templateMail);
    $send = $mailer->Send();
    if ( $send !== true ) {
        JLog::add(__METHOD__.__CLASS__.$templateMail.__line__,
            JLOG::ERROR
        );
    } else {
         'Mail sent';
    }

}

/*$body='<ul style="    
    list-style: none;
    max-width: 450px;
    padding: 10px;">
    <li><h2 style="    background: #FF9400; text-align: center; color: white;">Замовлення <b style="">№ 55517493</b><br> на суму 1500 грн. підтверджено</h2></li>
    <li><p>Доброго дня, Арсенюк Катерина Валеріївна! Якщо у Вас з’явилися питання або виникли труднощі з оплатою - звертайтеся у Контакт-центр за телефоном: <br><span><a href="tel:0954087707">(095) 408 77 07</a></span></p></li>
    <li>Сайт <a href="https://dobro-clinic.com" target="_blank">dobro-clinic.com</a></li>
</ul>
<h1>Тестовий платіж</h1>';

mailInvoice(['sorkin19822@ukr.net'],$body);*/
