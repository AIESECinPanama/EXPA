<?php
if($_SERVER['REQUEST_METHOD'] === 'get' || empty($_POST)) {
    header("Location: http://aiesec.org.pa");
    return;
}

//podio library
include_once 'wp-content/plugins/EXPAGRPodio-WPplugin-master/lib/podio-php-master/PodioAPI.php';

//private keys config files
$configs_external = include('wp-content/plugins/EXPAGRPodio-WPplugin-master/wp_login_config.php');

//plugin configs
$configs = include('wp-content/plugins/EXPAGRPodio-WPplugin-master/config.php');

// pre procesamiento de los datos
$_POST = $_POST['fields'];

$_POST['first-name'] = trim($_POST['first-name']);
$_POST['last-name'] = trim($_POST['last-name']);

$_POST['first-name'] = preg_replace('/\s+/', ' ', $_POST['first-name']);
$_POST['last-name'] = preg_replace('/\s+/', ' ', $_POST['last-name']);


$telefonos = array();
$phone = '';
$cell = false;

for($i=0; $i<count($_POST['telefono']); $i+=2) {
    $telefonos[] = array(
        'type' => $_POST['telefono'][$i]['type'],
        'value' => $_POST['telefono'][$i + 1]['value']
    );

    if(!$cell) {
        $phone = $_POST['telefono'][$i + 1]['value'];

        if($_POST['telefono'][$i]['type'] === 'mobile') {
            $cell = true;
        }
    }
}

$_POST['telefono'] = $telefonos;

/*
function printArray($array){
     foreach ($array as $key => $value){
        echo "$key => $value";
        echo "<br>";
        if(is_array($value)){ //If $value is an array, print it as well!
            printArray($value);
        }  
    } 
}

printArray($_POST);
return;*/


/**
* AIESEC GIS Form Submission via cURL
* 
* This is a basic form processor to create new users for the Opportunities Portal
* so you can create and manage a registration form on your country website.
*
* 
*/

// UNCOMMENT HERE: to view the HTML form requested from the GIS
//print $result;


$curl = curl_init();
// Set some options - we are passing in a useragent too here
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => 'https://auth.aiesec.org/users/sign_in',
    CURLOPT_USERAGENT => 'Codular Sample cURL Request'
    ));
// Send the request & save response to $resp
$result = curl_exec($curl);


// Close request to clear up some resources
curl_close($curl);

// extract token from cURL result
preg_match('/<meta content="(.*)" name="csrf-token" \/>/', $result, $matches);
$gis_token = $matches[1];


// UNCOMMENT HERE: to view HTTP status and errors from curl
// curl_errors($ch1);

//close connection
//curl_close($ch1);


// structure data for GIS
// form structure taken from actual form submission at auth.aiesec.org/user/sign_in

$fields = array(
    'authenticity_token' => htmlspecialchars($gis_token),
    'user[email]' => htmlspecialchars($_POST['correo-electronico']),
    'user[first_name]' => htmlspecialchars($_POST['first-name']),
    'user[last_name]' => htmlspecialchars($_POST['last-name']),
    'user[password]' => htmlspecialchars($_POST['password']),
    'user[phone]' => htmlspecialchars($phone),
    'user[country]' => $configs["country_name"], //'POLAND', // EXAMPLE: 'GERMANY' 
    'user[mc]' => $configs["mc_id"], //'1626', // EXAMPLE: 1596
    'user[lc_input]' => $_POST['localcommittee'],
    'user[lc]' => $_POST['localcommittee'],
    'commit' => 'REGISTER'
    );


// UNCOMMENT HERE: to view the array which will be submitted to GIS
// echo "<h2>Text going to GIS</h2>";
// echo '<pre>';
// print_r($fields);
// echo "</pre>";

//url-ify the data for the POST
$fields_string = "";
foreach($fields as $key=>$value) { $fields_string .= $key.'='.urlencode($value).'&'; }
rtrim($fields_string, '&');
$innerHTML = "";
// UNCOMMENT THIS BLOCK: to enable real GIS form submission


// POST form with curl
$url = "https://auth.aiesec.org/users";
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $url);
curl_setopt($ch2, CURLOPT_POST, count($fields));
curl_setopt($ch2, CURLOPT_POSTFIELDS, $fields_string);

curl_setopt($ch2, CURLOPT_RETURNTRANSFER, TRUE);
// give cURL the SSL Cert for Salesforce
//curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false); // TODO: FIX SSL - VERIFYPEER must be set to true
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);

//
// "without peer certificate verification, the server could use any certificate,
// including a self-signed one that was guaranteed to have a CN that matched 
// the server’s host name."
// http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
// 
// curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 2);
// curl_setopt($ch2, CURLOPT_CAINFO, getcwd() . "\CACerts\VeriSignClass3PublicPrimaryCertificationAuthority-G5.crt");
$result = curl_exec($ch2);

curl_errors($ch2);
// Check if any error occurred
if (curl_errno($ch2)) {

    curl_close($ch2);
    header("Location: http://aiesec.org.pa/?registro=no");
    return;
}


curl_close($ch2);



libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTML($result);    
libxml_clear_errors();
$selector = new DOMXPath($doc);

$result = $selector->query('//div[@id="error_explanation"]');


$children = $result->item(0)->childNodes;
if (is_iterable($children))
{
    foreach ($children as $child) {
        $tmp_doc = new DOMDocument();
        $tmp_doc->appendChild($tmp_doc->importNode($child,true));  
        $innerHTML .= strip_tags($tmp_doc->saveHTML());
        //$innerHTML.add($tmp_doc->saveHTML());
    }
}

$innerHTML = preg_replace('~[\r\n]+~', '', $innerHTML);
$innerHTML = str_replace(array('"', "'"), '', $innerHTML);

///////////PODIO Start /////////
///////////PODIO Start /////////
///////////PODIO Start /////////
///////////PODIO Start /////////
///////////PODIO Start /////////

function array_to_int($arr) {
    for($i=0; $i<count($arr); $i++) {
        $arr[$i] = intval($arr[$i]);
    }
    return $arr;
}

//Podio submit
// This is to test the conection with the podio API and the authentication
Podio::setup($configs_external['podio_client_id'], $configs_external['podio_client_secret']);
$podio_id = 1;

try {

    Podio::authenticate_with_app(intval($configs_external['podio_app_id']), $configs_external['podio_app_token']);
    $podio_id = intval($configs_external['podio_app_id']);

    $fields = new PodioItemFieldCollection(array(
    new PodioTextItemField(array(
        "external_id" => "titulo",
        "values" => $_POST['first-name'] . ' ' . $_POST['last-name']
    )),
    new PodioPhoneItemField(array(
        "external_id" => "telefono",
        "values" => $_POST['telefono']
    )),
    new PodioTextItemField(array(
        "external_id" => "correo-electronico",
        "values" => $_POST['correo-electronico']
    )),
    new PodioTextItemField(array(
        "external_id" => "pais",
        "values" => $_POST['pais']
    )),
    new PodioCategoryItemField(array(
        "external_id" => "genero",
        "values" => intval($_POST['genero'])
    )),
    new PodioCategoryItemField(array(
        "external_id" => "cuales-son-las-tres-areas-de-impacto-en-las-que-estas-i",
        "values" => array_to_int($_POST['cuales-son-las-tres-areas-de-impacto-en-las-que-estas-i'])
    )),
    new PodioTextItemField(array(
        "external_id" => "cual-es-tu-estado-o-ciudad-de-residencia",
        "values" => $_POST['cual-es-tu-estado-o-ciudad-de-residencia']
    )),
    new PodioCategoryItemField(array(
        "external_id" => "cual-es-tu-estado-academico-actual",
        "values" => intval($_POST['cual-es-tu-estado-academico-actual'])
    )),
    new PodioTextItemField(array(
        "external_id" => "nombre-de-la-universidad-en-la-que-estudias-solo-si-apl",
        "values" => $_POST['nombre-de-la-universidad-en-la-que-estudias-solo-si-apl']
    )),
    new PodioCategoryItemField(array(
        "external_id" => "cual-es-tu-pais-de-interes",
        "values" => array_to_int($_POST['cual-es-tu-pais-de-interes'])
    )),
    new PodioTextItemField(array(
        "external_id" => "texto",
        "values" => $_POST['texto']
    )),
    new PodioCategoryItemField(array(
        "external_id" => "como-te-enteraste-de-aiesec",
        "values" => intval($_POST['como-te-enteraste-de-aiesec'])
    )),
    new PodioCategoryItemField(array(
        "external_id" => "por-que-te-gustaria-realizar-tu-voluntariado-con-aiesec",
        "values" => array_to_int($_POST['por-que-te-gustaria-realizar-tu-voluntariado-con-aiesec'])
    ))));



    // Create the item object with fields
    // Be sure to add an app or podio-php won't know where to create the item
    $item = new PodioItem(array(
      'app' => new PodioApp($podio_id), // Attach to app with app_id=123
      'fields' => $fields
      ));

    // Upload files
    $_FILES = $_FILES['attachments'];
    //print_r($_FILES);

    if(!empty($_FILES) && !empty($_FILES['name'][0])) {
        $form_files = array();

        for($i=0; $i<count($_FILES['name']); $i++) {
            $form_files[] = PodioFile::upload($_FILES['tmp_name'][$i], $_FILES['name'][$i]);
        }

        $item->files = new PodioCollection($form_files);
    }

    // Save the new item
    $item->save();
}
catch (PodioError $e) {
  // Something went wrong. Examine $e->body['error_description'] for a description of the error.
    //echo $e->body['error_description'];
    header("Location: http://aiesec.org.pa/?registro=no");
    return;
}


////////PODIO END /////////
////////PODIO END /////////
////////PODIO END /////////
////////PODIO END /////////
////////PODIO END /////////
////////PODIO END /////////


function is_iterable($var)
{
    return $var !== null 
    && (is_array($var) 
        || $var instanceof Traversable 
        || $var instanceof Iterator 
        || $var instanceof IteratorAggregate
        );
}

function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
   else if(getenv('REMOTE_ADDR'))
    $ipaddress = getenv('REMOTE_ADDR');
else
    $ipaddress = 'UNKNOWN';
return $ipaddress;
}


//// HUBSPOT ////
//// HUBSPOT ////
//// HUBSPOT ////

try {
    $arr = array(
        'properties' => array(
            array(
                'property' => 'email',
                'value' => $_POST['correo-electronico']
            ),
            array(
                'property' => 'firstname',
                'value' => $_POST['first-name']
            ),
            array(
                'property' => 'lastname',
                'value' => $_POST['last-name']
            ),
            array(
                'property' => 'phone',
                'value' => $phone
            )
        )
    );

    $post_json = json_encode($arr);
    //$hapikey = readline("EXPA new contact");
    $endpoint = 'https://api.hubapi.com/contacts/v1/contact?hapikey=' . $configs_external['hapikey'];
    $ch = @curl_init();
    @curl_setopt($ch, CURLOPT_POST, true);
    @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
    @curl_setopt($ch, CURLOPT_URL, $endpoint);
    @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = @curl_exec($ch);
    $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errors = curl_error($ch);
    @curl_close($ch);
   // echo "curl Errors: " . $curl_errors;
   // echo "\nStatus code: " . $status_code;
   // echo "\nResponse: " . $response;
}
catch(Exception $e) {

   // header("Location: http://aiesec.org.pa/?registro=no");
   // return;
}

//// HUBSPOT ////
//// HUBSPOT ////
//// HUBSPOT ////


header("Location: http://aiesec.org.pa/?registro=yes");


function curl_errors($ch) {
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno= curl_errno($ch);
}
