<?php
define( '_JEXEC', 1 );
if (!defined( 'DS' )) {
    define( 'DS', DIRECTORY_SEPARATOR );
}


if ( file_exists( $_SERVER['DOCUMENT_ROOT'].'/defines.php' ) ) {
    include_once $_SERVER['DOCUMENT_ROOT'].'/defines.php';
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
    include_once JPATH_BASE .DS."modules".DS."mod_paypb".DS."config.php";
}

class service {

    private $responseDataArray;
    private $responseDataJson;
    public $connect;
    public $array;
    public $jinput;
    public $tokenGoogle;
    public $moduleInstance;
    public function __construct(){
        $this->connect = JFactory::getDbo();
        $this->jinput = Joomla\CMS\Factory::getApplication('site')->input;
        $this->moduleInstance = JModuleHelper::getModule('mod_paypb');

    }

    public function sendGuzzle($order){
        JLog::addLogger(array('text_file' => 'mod_paypb-'.date ( 'Y-F' ).'.php'));
        define( 'PAYMENT', "<h4 class='uk-align-center uk-margin-top' style='color: green;max-width: 215px'>Счет уже оплачен</h4>" );
        define( 'NOT_FOUND', "<h4 class='uk-align-center uk-margin-top' style='color: red;max-width: 215px'>Данные не найдены, проверьте правильность ввода</h4>" );
        define( 'NOT_TEMPORARY', "<h4 class='uk-align-center uk-margin-top' style='color: red;max-width: 215px'>Сервис временно недоступен, поробуйте позже</h4>" );
        define( 'NOT_VALIDATION', "<h4 class='uk-align-center uk-margin-top' style='color: red;max-width: 215px'>Вы не прошли валидацию</h4>" );
        $array = $order;
        $client = new \GuzzleHttp\Client(['base_uri' => 'http://192.168.122.1']);
        try {
            $res = $client->request('POST', '/sh/hs/pay/', [
                    'allow_redirects' => [
                        'max'             => 5,        // allow at most 10 redirects.
                    ],
                    'connect_timeout' => 6.14,
                    'auth' => ['login', 'passv'],
                    'Content-Type'=>'application/json',
                    'Cache-Control' => 'no-cache',
                    GuzzleHttp\RequestOptions::JSON => $array
                ]
            );

            $this->responseDataJson = $res->getBody()->getContents();
            $this->responseDataArray = json_decode($this->responseDataJson,true);
            return $this->responseDataJson;

        } catch (GuzzleHttp\Exception\RequestException $e) {
            $requestText = GuzzleHttp\Psr7\str($e->getRequest());

            JLog::add($requestText,
                JLOG::ERROR
            );
            if ($e->hasResponse()) {
                $responseText = GuzzleHttp\Psr7\str($e->getResponse());
                JLog::add($responseText,
                    JLOG::ERROR
                );
                return false;
            }
        }

    }



    public function renderForm($order=0, $summ=0, $href=0, $patient=0) {
        $session = JFactory::getSession();
        $psws_sess = $session->getId();
        $href = base64_decode($href);
        $return_url_server_pb = JUri::base().'/modules/mod_paypb/paypb.php';
        $sandbox = $this->getParamsModule('sandBox');
        $privateKey = $this->getParamsModule('privateKey');
        $publikKey = $this->getParamsModule('publikKey');
        $liqpay = new LiqPay($publikKey, $privateKey);
        return $html = $liqpay->cnb_form(array(
            'action'         => 'pay',
            'amount'         => $summ,
            'currency'       => 'UAH',
            'description'    => 'За медичні послуги №'.$order.' '.$patient,
            'order_id'       => $order,
            'version'        => '3',
            'language'		=>	'uk', // uk, en
            'server_url'	=>	$return_url_server_pb.'?order_id='.$order,
            'sandbox'       => $sandbox,
            'result_url'    =>$href
        ));
    }

    public function getData($indexAssoc='0'){

        switch ($indexAssoc){
            case 'total':
                return $this->responseDataArray['data'][0]['total'];
                break;
            case 'date':
                return $this->responseDataArray['data'][0]['date'];
                break;
            case 'patient':
                return $this->responseDataArray['data'][0]['patient'];
                break;
        }

        return $this->responseDataArray;
    }
    public function convertStrToDate($str){
        $time = strtotime($str);
        $newformat = date('d.m.Y',$time);
        return $newformat;
    }

    public function insertDataOrder($array){
        if(!empty($array)){
            JLog::addLogger(array('text_file' => 'mod_paypb-pay-'.date ( 'Y-F' ).'.php'));
        try {
            $result = $this->connect->insertObject('#__paypb',$array);
            $query = $this->connect->getQuery(TRUE);
        }
        catch (Exception $e){
            JLog::add($e->getMessage(),
                JLOG::ERROR
            );

        }
    }
    }

    public function deleteDataOrder($order){
        $db = $this->connect;

        $query = $db->getQuery(true);

        $conditions = array(
            $db->quoteName('invoice') . ' = '.$db->quote($order),
            $db->quoteName('sendData') . ' < ' . $db->quote('1')
        );

        $query->delete($db->quoteName('#__paypb'));
        $query->where($conditions);

        $db->setQuery($query);

        $result = $db->execute();

    }

    public function validateOrder($order, $seccode){
        $orderServ = $this->jinput->get('order', null);
        $hashServ = $this->jinput->get('hashServ', null);
        if ($order==$orderServ){
            if($seccode!=$hashServ){die(NOT_FOUND);}else{}
        }else {return;}
    }

    /**
     * is valid Google Recaptcha
     * @var $params get Params of plugins "recaptcha_invisible"
     * @return bool
     */
    public function isPeople(){
        $pluginCapthaEnable = Joomla\CMS\Plugin\PluginHelper::getPlugin('captcha','recaptcha_invisible');
        if(empty($pluginCapthaEnable)){return true;}
        $params = json_decode($pluginCapthaEnable->params);
        $recaptcha = new \ReCaptcha\ReCaptcha($params->private_key);
        $resp = $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME'])
            ->setExpectedAction('homepage')
            ->setScoreThreshold(0.5)
            ->verify($this->tokenGoogle, $_SERVER['REMOTE_ADDR']);
        $result = $resp->toArray();
        return $result['success'];
    }

    public function getParamsModule($property=null){
        $params = json_decode($this->moduleInstance->params);
        if(!empty($property)){
            return $params->$property;
        }else{
            return $params;
        }


    }

}


$str = new service();

if ($_SERVER['REQUEST_METHOD'] == 'POST' & !empty($_POST['order'])){
    $order = $str->jinput->get('order', null, 'ALNUM');
    $emailUser = $str->jinput->get('email', null, 'STRING');
    $str->tokenGoogle = $str->jinput->get('token', null, 'STRING');
    $href = filter_input( INPUT_POST, 'href', FILTER_SANITIZE_SPECIAL_CHARS);
    $seccode = $str->jinput->get('seccode', null, 'ALNUM');

    /*Валидация робота*/
    if(!$str->isPeople()){
        die(YOU_ARE_ROBOTS);
    };


    //var_dump($seccode, $order,$emailUser,$href);
    if ($order)
    {
        $arr = ['order'=>$order];

        $json = $str->sendGuzzle($arr);
        $arr = json_decode($json, true);
        $str->deleteDataOrder($order);
        switch ($arr['status']){

            case 1:
                $str->jinput->def('hashServ',$arr['data'][0]['seccode']);
                $str->jinput->def('order',$order);
                $orderServ = $str->jinput->get('order', null);
                $hashServ = $str->jinput->get('hashServ', null);
                $str->validateOrder($order, $seccode);
                //var_dump($orderServ,$hashServ);die();
/*                $hashServ = hash('sha256', $arr['data'][0]['seccode']);
                $hashUser = hash('sha256', $seccode);
                ($hashUser==$hashServ)? die('super'):die('no super');
                $str->session->set( 'secureCode', $hashServ );
                $str->session->set( 'order', $order );*/

                $total = $str->getData('total');
                $html = $str->renderForm(
                    $order,
                    $str->getData('total'),
                    $href,
                    $str->getData('patient')
                );
                $arr['data'][]=array('button'=>$html);

                //Добавление в массив возврата номер счета
                $arr['data'][0]['order']=$order;
                //Обработка в правильную дату из 1С
                $arr['data'][0]['date'] = $str->convertStrToDate($arr['data'][0]['date']);

                /*Вставка в БД*/

                $invoice = new stdClass();
                $invoice->invoice = $order;
                $invoice->userEmail = $emailUser;
                $invoice->fio = $str->getData('patient');
                $invoice->summ = $str->getData('total');
                $invoice->createAT = $date = date('Y-m-d H:i:s');

                $str->insertDataOrder($invoice);

                echo json_encode($arr['data'], JSON_UNESCAPED_UNICODE);
                break;
            case 2:
                echo NOT_FOUND;
                break;
            case 3:
                echo PAYMENT;
                break;
            default:
                echo NOT_TEMPORARY;
        }
    }


}else{
    return false;
}



die();
