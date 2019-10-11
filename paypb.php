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
    require_once JPATH_BASE .DS."modules".DS."mod_paypb".DS."config.php";
}

if ( file_exists( JPATH_BASE .DS."modules".DS."mod_paypb".DS.'tpl'.DS."templateMail.php") ) {
    require_once JPATH_BASE .DS."modules".DS."mod_paypb".DS."tpl".DS."templateMail.php";
}

function sendStatus($invoice)
{
    $client = new \GuzzleHttp\Client(['base_uri' => 'http://192.198.194.217:1984']);
    $array[]=$invoice;
    try {
        $res = $client->request('PUT', '/sh/hs/pay/', [
                'allow_redirects' => [
                    'max' => 5,        // allow at most 10 redirects.
                ],
                'connect_timeout' => 6.14,
                'auth' => ['login', 'passw'],
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
                GuzzleHttp\RequestOptions::JSON => $array
            ]
        );
        $responseDataJson = $res->getBody()->getContents();
        $responseDataArray = json_decode(responseDataJson, true);
        return $responseDataJson;
    } catch (GuzzleHttp\Exception\RequestException $e) {
        $requestText = GuzzleHttp\Psr7\str($e->getRequest());
		        JLog::add($e->hasResponse().__LINE__,
            JLOG::ERROR
        );
        if ($e->hasResponse()) {
            $responseText = GuzzleHttp\Psr7\str($e->getResponse());

            return false;
        }
    }
}

function selectData($invoice='1236'){
    $db = JFactory::getDbo();

    $query = $db->getQuery(true);

    $query->select($db->quoteName(array('id','invoice','fio','summ','userEmail','createAT', 'payAT')));
    $query->from($db->quoteName('#__paypb'));
    $query->where($db->quoteName('invoice') . ' LIKE '. $db->quote($invoice));
    $query->order('createAT DESC');

// Reset the query using our newly populated query object.
    $db->setQuery($query);

// Load the results as a list of stdClass objects (see later for more options on retrieving data).
    $results = $db->loadObjectList();
    return $results[0];
}

class responseBank {
    public $connect;
    public $array;
    public $jinput;
    public $moduleInstance;
    public function __construct(){
        $this->connect = JFactory::getDbo();
        $this->jinput = Joomla\CMS\Factory::getApplication('site')->input;
        $this->moduleInstance = JModuleHelper::getModule('mod_paypb');

    }
    public function getParamsModule($property=null){
        $params = json_decode($this->moduleInstance->params);
        if(!empty($property)){
            return $params->$property;
        }else{
            return $params;
        }
    }

    public function validSignature($data, $signature){
        $sign = base64_encode( sha1(
            $this->getParamsModule('privateKey') .
            $data .
            $this->getParamsModule('privateKey')
            , 1 ));

        return ($signature == $sign)? true : false;
    }

}

if ($_SERVER['REQUEST_METHOD'] == 'POST' & !empty($_POST['signature'])){

    JLog::addLogger(array('text_file' => 'mod_paypb-response-'.date ( 'Y-F' ).'.php'));

    $bankResponseSignature = $_POST['signature'];
    $bankResponseData = $_POST['data'];
	
	$str = new responseBank();
    $val = $str->validSignature($bankResponseData, $bankResponseSignature);

    if($val){

        JLog::add(base64_decode($_POST['data']).__LINE__,
            JLOG::INFO
        );

        $response= json_decode( base64_decode($_POST['data']),true);
        $content = sprintf('%s%s[%s] Received new POST data!%s', PHP_EOL, PHP_EOL, date('r'), PHP_EOL);
		//$content .= $val1.'---'.$val.'----'. PHP_EOL;
        $content .= var_export($response, TRUE) . PHP_EOL;
        


        $status = $response['status'];
        $timestamp = (int)$response['create_date']/1000;
        $checkOutTime = date("Y-m-d H:i:s", $timestamp);
        $invoice = $response['order_id'];

        if (($response['status'])=='success'){
            $responseC = sendStatus($invoice);
            if($responseC){
                $status = $responseC;
            }
            else
                {$status = DEAD1C;};

        }



        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('id','invoice','summ','userEmail','createAT', 'payAT')));
        $query->from($db->quoteName('#__paypb'));
        $query->where($db->quoteName('invoice') . ' LIKE '. $db->quote($invoice));
        $query->order('createAT DESC');

// Reset the query using our newly populated query object.
        $db->setQuery($query);

// Load the results as a list of stdClass objects (see later for more options on retrieving data).
        $results = $db->loadResult();
        if($results){
            
                $object = new stdClass();
// Must be a valid primary key value.
                $object->sendData = $status;
                $object->id = $results;
                $object->payAT = $checkOutTime;
                $object->response = base64_decode($_POST['data']);

// Update their details in the users table using id as the primary key.
                $result = JFactory::getDbo()->updateObject('#__paypb', $object, 'id');
				$invoiceData = selectData($invoice);
				$content .= var_export($invoiceData, TRUE) . PHP_EOL;
		if(!empty($invoiceData)){
			$bodyMail = mailTemplate($invoiceData, $statusPAy[$response['status']]);
			$content .= var_export($bodyMail, TRUE) . PHP_EOL;
			$content .= var_export($mailAdmin, TRUE) . PHP_EOL;
			try {
            
                    mailInvoice($invoiceData->userEmail,$bodyMail);
					mailInvoice($mailAdmin,$bodyMail);
					/*mailInvoice($invoiceData->userEmail,$bodyMail);
                    /*JLog::add(base64_decode($_POST['data']).__LINE__,
                        JLOG::INFO
                    );*/

            }catch (Exception $e){

                JLog::add(__METHOD__.$e->getMessage().__LINE__,
                    JLOG::ERROR
                );
				 }
			
			$historyObject = new stdClass();
                $historyObject->id_paypb = $results;
                $historyObject->response = base64_decode($_POST['data']);
                $result = JFactory::getDbo()->insertObject('#__paypb_response', $historyObject);		
				 
				 
				 
		}
			
			
        }

    }
file_put_contents(sprintf('%s/api-call-completed.log', __DIR__), $content, FILE_APPEND);
}