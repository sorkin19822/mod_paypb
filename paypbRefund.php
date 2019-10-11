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

if ( file_exists( JPATH_BASE .DS."modules".DS."mod_paypb".DS.'tpl'.DS."templateMail.php") ) {
    include_once JPATH_BASE .DS."modules".DS."mod_paypb".DS."tpl".DS."templateMail.php";
}

function sendStatus($array= ['order' => '180', 'status' => '1'])
{
    $client = new \GuzzleHttp\Client(['base_uri' => 'http://192.198.122.1:1984']);
    $array = $array;
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

        if ($e->hasResponse()) {
            $responseText = GuzzleHttp\Psr7\str($e->getResponse());

            return false;
        }
    }
}

function validSignature($data, $signature){
    $sign = base64_encode( sha1(
        PRIVATE_KEY .
        $data .
        PRIVATE_KEY
        , 1 ));

    return ($signature == $sign)? true : false;
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

JLog::addLogger(array('text_file' => 'mod_paypb-response-'.date ( 'Y-F' ).'.php'));

if ($_SERVER['REQUEST_METHOD'] == 'POST' & ($_POST['refund']=='1') & !empty($_POST['order']) & !empty($_POST['summ'])){


    $public_key = PUBLIK_KEY;
    $private_key= PRIVATE_KEY;
    $sandbox = $params['liqpay_sandbox'];
    $liqpay = new LiqPay($public_key, $private_key);
    $res = $liqpay->api("request", array(
        'action'        => 'refund',
        'version'       => '3',
        'sandbox'       => '0',
        'order_id'      => $_POST['order'],
        'amount'        =>'1'
    ));

    $data = json_encode((array)$res, JSON_UNESCAPED_UNICODE);
    echo $data;

    JLog::add($data,
        JLOG::INFO
    );

    //var_dump($res->err_description);

    //Считывание заголовков
    /*    foreach (getallheaders() as $name => $value) {
        echo "$name: $value\n";
    }*/
//refund=1&order=55517482&summ=1

    die();



}


//refund=1&order=55517482&summ=1
