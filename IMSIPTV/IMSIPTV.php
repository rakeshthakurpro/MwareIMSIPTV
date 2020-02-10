<?php
/**
 * MwarePanel IPTV service provisioning module
 * Developer : Rakesh Kumar
 * Email : whmcsninja@gmail.com
 * Website : whmcsninja.com
 * */
use WHMCS\Database\Capsule;

/**
 * WHMCS cloudtv Module
 */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * @see https://developers.whmcs.com/provisioning-modules/meta-data-params/
 * @return array
 */
function IMSIPTV_MetaData() {
    return array(
        'DisplayName' => 'IMS IPTV Module',
        'APIVersion' => '1.0'
    );
}

/**
 * Define product configuration options.
 * @see https://developers.whmcs.com/provisioning-modules/config-options/
 * @return array
 */
function IMSIPTV_ConfigOptions() {

    return array(
        'IMSurl' => array(
            'Type' => 'text',
            'Size' => '40',
            'Default' => 'https://cloudtv.mware-solutions.com/',
            'Description' => 'IMS server url',
        ),
        'CMSName' => array(
            'Type' => 'text',
            'Size' => '40',
            'Default' => 'Test_CRM',
            'Description' => 'CMS name',
        ),
        'CrmService' => array(
            'Type' => 'text',
            'Size' => '40',
            'Default' => '',
            'Description' => 'The CRM identifier name where the customer needs to be added, you can find this name on the main screen of the IMS',
        ),
        'AuthToken' => array(
            'Type' => 'text',
            'Size' => '40',
            'Default' => '',
            'Description' => 'The authToken can be obtained in CRM > Settings > Company Info you need it to make a valid request',
        ),
        'ProductId' => array(
            'Type' => 'dropdown',
            'Options' => IMSIPTV_getProduct($_REQUEST['id']),
            'Description' => 'This needs to be a productId from the CRM part of the IMS and it has to have at least 1 payment plan and 1 currrency set for it',
        ),
        'StartSubscriptionFromFirstLogin' => array(
            'Type' => 'dropdown',
            'Options' => array(
                'true' => 'Yes',
                'false' => 'No'
            ),
            'Default' => 'false',
            'Description' => '',
        ),
        'sendMail' => array(
            'Type' => 'dropdown',
            'Options' => array(
                'true' => 'Yes',
                'false' => 'No'
            ),
            'Default' => 'false',
            'Description' => '',
        ),
        'sendSMS' => array(
            'Type' => 'dropdown',
            'Options' => array(
                'true' => 'Yes',
                'false' => 'No'
            ),
            'Default' => 'false',
            'Description' => '',
        ),
        "license_key" => array(
            "FriendlyName" => "Liecence Key",
            "Type" => "text",
            "Size" => "50",
        ),
         "Reseller_ID" => array(
            "FriendlyName" => "Reseller ID",
             "Default" => "0", 
            "Type" => "text",
            "Size" => "50",
        ),
        "Reseller_Plan" => array(
            "FriendlyName" => "Reseller Plan",
             "Default" => "", 
            "Type" => "text",
            "Size" => "50",
            'Description' => 'Order ID From IMS Pannel',
        ),
    );
}

/**
 * Provision a new instance of a product/service.
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @return string "success" or an error message
 */
function IMSIPTV_CreateAccount(array $params) {
    try {
       
    //    $licenseinfo = IMSIPTV_doCheckLicense($params['configoption9']);
       // if ($licenseinfo['status'] != 'Active') {
    //        return "Your license is " . $licenseinfo['status'];
     //   }
        $data['cmsService'] = $params['configoption2'];
        $data['crmService'] = $params['configoption3'];
        $data['authToken'] = $params['configoption4'];
        $data['reseller_id']=$params['configoption10'];
        $data['order_id']=$params['configoption11'];
        $data['firstname'] = $params['clientsdetails']['firstname'];
        $data['lastname'] = $params['clientsdetails']['lastname'];
        $data['street'] = $params['clientsdetails']['address1'];
        $data['zipcode'] = $params['clientsdetails']['postcode'];
        $data['city'] = $params['clientsdetails']['city'];
        $data['state'] = $params['clientsdetails']['state'];
        $data['country'] = IMSIPTV_country($params['clientsdetails']['country']);
        $data['phone'] = $params['clientsdetails']['phonenumber'];
        $data['mobile'] = '+' . $params['clientsdetails']['phonecc'] . $params['clientsdetails']['phonenumber'];
        $data['email'] = $params['clientsdetails']['email'];
        $data['productid'] = $params['configoption5'];
        $data['customermappingid'] = $params['clientsdetails']['userid'];
        $currency = Capsule::table('tblcurrencies')->where('id', $params['clientsdetails']['currency'])->first();
        $data['currency'] = $currency->code;
        $billingcycle = $params['model']['billingcycle'];
        switch ($billingcycle) {
            case"One Time":
                $suscriptionlength = 1;
                $renewalinterval = 1;
                break;
            case"Free Account":
                $suscriptionlength = 1;
                $renewalinterval = 1;
                break;
            case"Monthly":
                $suscriptionlength = 1;
                $renewalinterval = 1;
                break;
            case"Quarterly":
                $suscriptionlength = 3;
                $renewalinterval = 3;
                break;
            case"SemiAnnually":
                $suscriptionlength = 6;
                $renewalinterval = 6;
                break;
            case"Annually":
                $suscriptionlength = 12;
                $renewalinterval = 12;
                break;
            case"Biennially":
                $suscriptionlength = 24;
                $renewalinterval = 24;
                break;
            case"Triennially":
                $suscriptionlength = 36;
                $renewalinterval = 36;
        }

        $data['subscriptionlengthindays'] = 0;
        $data['subscriptionlengthinmonths'] = $suscriptionlength;
        $data['renewalinterval'] = $renewalinterval;
        $data['StartSubscriptionFromFirstLogin'] = $params['configoption6'];
        $data['sendMail'] = $params['configoption7'];
        $data['sendSMS'] = $params['configoption8'];
        //$data['uuid'] = $params['customfields']['MacAddress'];
        $url = $params['configoption1'] . '/api/AddCustomer/addCustomerAsync?' . http_build_query($data);
        $resp = IMSIPTV_sendData($url, 'post');
        $jsonData = json_decode($resp, true);
        $command = 'UpdateClientProduct';
        $postData = array(
            'serviceid' => $params['serviceid'],
            'serviceusername' => $jsonData['loginId'],
            'servicepassword' => $jsonData['password']
        );
        $adminUsername = IMSIPTV_get_admin(); // Optional for WHMCS 7.2 and later
        $results = localAPI($command, $postData, $adminUsername);
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $data, $resp
        );
        if(isset($jsonData['Message'])){
            return $jsonData['Message'];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );
        return $e->getMessage();
    }

    return 'success';
}

/**
 * Suspend an instance of a product/service.
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @return string "success" or an error message
 */
function IMSIPTV_SuspendAccount(array $params) {
    try {
   //     $licenseinfo = IMSIPTV_doCheckLicense($params['configoption9']);
      //  if ($licenseinfo['status'] != 'Active') {
    //        return "Your license is " . $licenseinfo['status'];
      //  }
        $data['customermappingid'] = $params['clientsdetails']['userid'];
        $data['status'] = 'Disabled';
        $data['password'] = $params['password'];
        $data['cmsService'] = $params['configoption2'];
        $data['crmService'] = $params['configoption3'];
        $data['authToken'] = $params['configoption4'];
        $data['userid'] = $params['username'];
        $url = $params['configoption1'] . 'api/DisableCustomer/changeCustomerStatus?' . http_build_query($data);
        $resp = IMSIPTV_sendData($url, 'post');
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $data, $resp
        );
        $jsonData = json_decode($resp, true);
        if(isset($jsonData['Message'])){
            return $jsonData['Message'];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Un-suspend instance of a product/service.
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @return string "success" or an error message
 */
function IMSIPTV_UnsuspendAccount(array $params) {
    try {
     //   $licenseinfo = IMSIPTV_doCheckLicense($params['configoption9']);
      //  if ($licenseinfo['status'] != 'Active') {
    //        return "Your license is " . $licenseinfo['status'];
      //  }
        $data['customermappingid'] = $params['clientsdetails']['userid'];
        $data['password'] = $params['password'];
        $data['cmsService'] = $params['configoption2'];
        $data['crmService'] = $params['configoption3'];
        $data['authToken'] = $params['configoption4'];
        $data['userid'] = $params['username'];
        $url = $params['configoption1'] . 'api/EnableCustomer/enableCustomer?' . http_build_query($data);
        $resp = IMSIPTV_sendData($url, 'post');
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $data, $resp
        );
        $jsonData = json_decode($resp, true);
        if(isset($jsonData['Message'])){
            return $jsonData['Message'];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );
        return $e->getMessage();
    }
    return 'success';
}

/**
 * Terminate instance of a product/service.
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @return string "success" or an error message
 */
function IMSIPTV_TerminateAccount(array $params) {
    try {
        $data['customermappingid'] = $params['clientsdetails']['userid'];
        $data['password'] = $params['password'];
        $data['cmsService'] = $params['configoption2'];
        $data['crmService'] = $params['configoption3'];
        $data['authToken'] = $params['configoption4'];
        $data['userid'] = $params['username'];
        $url = $params['configoption1'] . 'api/DeleteCustomer/trashCustomer?' . http_build_query($data);
        $resp = IMSIPTV_sendData($url, 'post');
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $data, $resp
        );
        $jsonData = json_decode($resp, true);
        if(isset($jsonData['Message'])){
            return $jsonData['Message'];
        }        
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Change the password for an instance of a product/service.
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @return string "success" or an error message
 */
function IMSIPTV_ChangePassword(array $params) {
    try {
     //   $licenseinfo = IMSIPTV_doCheckLicense($params['configoption9']);
      //  if ($licenseinfo['status'] != 'Active') {
    //        return "Your license is " . $licenseinfo['status'];
     //   }
        $data['password'] = $params['password'];
        $data['cmsService'] = $params['configoption2'];
        $data['crmService'] = $params['configoption3'];
        $data['authToken'] = $params['configoption4'];
        $data['userid'] = $params['username'];
        $url = $params['configoption1'] . 'api/EditCustomer/EditCustomer_V2?' . http_build_query($data);
        $resp = IMSIPTV_sendData($url, 'post');
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $data, $resp
        );
        $jsonData = json_decode($resp, true);
        if(isset($jsonData['Message'])){
            return $jsonData['Message'];
        }        
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function IMSIPTV_Renew(array $params) {
    try {
        $billingcycle = $params['model']['billingcycle'];
        switch ($billingcycle) {
            case"One Time":
                $suscriptionlength = 1;
                $renewalinterval = 1;
                break;
            case"Free Account":
                $suscriptionlength = 1;
                $renewalinterval = 1;
                break;
            case"Monthly":
                $suscriptionlength = 1;
                $renewalinterval = 1;
                break;
            case"Quarterly":
                $suscriptionlength = 3;
                $renewalinterval = 3;
                break;
            case"SemiAnnually":
                $suscriptionlength = 6;
                $renewalinterval = 6;
                break;
            case"Annually":
                $suscriptionlength = 12;
                $renewalinterval = 12;
                break;
            case"Biennially":
                $suscriptionlength = 24;
                $renewalinterval = 24;
                break;
            case"Triennially":
                $suscriptionlength = 36;
                $renewalinterval = 36;
        }
        $data['customermappingid'] = $params['clientsdetails']['userid'];
        $data['password'] = $params['password'];
        $data['cmsService'] = $params['configoption2'];
        $data['crmService'] = $params['configoption3'];
        $data['authToken'] = $params['configoption4'];
        $data['userid'] = $params['username'];
        $data['months'] = $renewalinterval;
        $data['days'] = 0;
        $currency = Capsule::table('tblcurrencies')->where('id', $params['clientsdetails']['currency'])->first();
        $data['currency'] = $currency->code;
        $data['fromExpireDate'] = 'true';
        $url = $params['configoption1'] . 'api/RenewCustomer/renewCustomerV2?' . http_build_query($data);
        $resp = IMSIPTV_sendData($url, 'post');
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $data, $resp
        );
        $jsonData = json_decode($resp, true);
        if(isset($jsonData['Message'])){
            return $jsonData['Message'];
        }        
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'IMSIPTV', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        return $e->getMessage();
    }
}

/**
 * Client area output logic handling.
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function IMSIPTV_ClientArea(array $params) {
    $data['customermappingid'] = $params['clientsdetails']['userid'];
    $data['password'] = $params['password'];
    $data['crmService'] = $params['configoption3'];
    $data['authToken'] = $params['configoption4'];
    $data['userid'] = $params['username'];
    $url = $params['configoption1'] . 'api/GetCustomer/getCustomer_V2?' . http_build_query($data);
    $resp = IMSIPTV_sendData($url, 'get');
    $jsonData = json_decode($resp, true);
    return array(
        'templatefile' => 'clientarea',
        'vars' => array(
            'customerdata' => $jsonData,
            'Username' => $params['username'],
            'Password' => $params['password'],
        ),
    );
}

function IMSIPTV_sendData($url, $method) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($method == 'post') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    }
    $headers = array();
    $headers[] = 'Accept: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    $respheader = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    if ($respheader == 400) {
        throw new Exception("BadRequest " . $result);
    }
    if ($respheader == 401) {
        throw new Exception("Unauthorized " . $result);
    }
    if ($respheader == 406) {
        throw new Exception("NotAcceptable " . $result);
    }
    if ($respheader == 500) {
        throw new Exception("Server Error " . $result);
    }
    curl_close($ch);
    return $result;
}

function IMSIPTV_getProduct($id) {
    try {
      	$product = Capsule::table('tblproducts')->where('servertype', 'IMSIPTV')->where('id',$id)->first();
      	if(!empty($product->configoption1)){
	        $url = $product->configoption1 . 'api/GetProducts/getProducts_V2?crmService=' . $product->configoption3 . '&authToken=' . $product->configoption4;
	        $resp = IMSIPTV_sendData($url, 'get');
	        $jsonData = json_decode($resp, true);
                $products = [];
	        foreach ($jsonData as $key => $value) {
	            $products[$value['ProductID']] = $value['ProductName'];
	        }
	     	return $products;   
       	}        
    } catch (Exception $e) {
        
    }
}

function IMSIPTV_country($code) {
    $countryArray = array(
        'AD' => array('name' => 'ANDORRA', 'code' => '376'),
        'AE' => array('name' => 'UNITED ARAB EMIRATES', 'code' => '971'),
        'AF' => array('name' => 'AFGHANISTAN', 'code' => '93'),
        'AG' => array('name' => 'ANTIGUA AND BARBUDA', 'code' => '1268'),
        'AI' => array('name' => 'ANGUILLA', 'code' => '1264'),
        'AL' => array('name' => 'ALBANIA', 'code' => '355'),
        'AM' => array('name' => 'ARMENIA', 'code' => '374'),
        'AN' => array('name' => 'NETHERLANDS ANTILLES', 'code' => '599'),
        'AO' => array('name' => 'ANGOLA', 'code' => '244'),
        'AQ' => array('name' => 'ANTARCTICA', 'code' => '672'),
        'AR' => array('name' => 'ARGENTINA', 'code' => '54'),
        'AS' => array('name' => 'AMERICAN SAMOA', 'code' => '1684'),
        'AT' => array('name' => 'AUSTRIA', 'code' => '43'),
        'AU' => array('name' => 'AUSTRALIA', 'code' => '61'),
        'AW' => array('name' => 'ARUBA', 'code' => '297'),
        'AZ' => array('name' => 'AZERBAIJAN', 'code' => '994'),
        'BA' => array('name' => 'BOSNIA AND HERZEGOVINA', 'code' => '387'),
        'BB' => array('name' => 'BARBADOS', 'code' => '1246'),
        'BD' => array('name' => 'BANGLADESH', 'code' => '880'),
        'BE' => array('name' => 'BELGIUM', 'code' => '32'),
        'BF' => array('name' => 'BURKINA FASO', 'code' => '226'),
        'BG' => array('name' => 'BULGARIA', 'code' => '359'),
        'BH' => array('name' => 'BAHRAIN', 'code' => '973'),
        'BI' => array('name' => 'BURUNDI', 'code' => '257'),
        'BJ' => array('name' => 'BENIN', 'code' => '229'),
        'BL' => array('name' => 'SAINT BARTHELEMY', 'code' => '590'),
        'BM' => array('name' => 'BERMUDA', 'code' => '1441'),
        'BN' => array('name' => 'BRUNEI DARUSSALAM', 'code' => '673'),
        'BO' => array('name' => 'BOLIVIA', 'code' => '591'),
        'BR' => array('name' => 'BRAZIL', 'code' => '55'),
        'BS' => array('name' => 'BAHAMAS', 'code' => '1242'),
        'BT' => array('name' => 'BHUTAN', 'code' => '975'),
        'BW' => array('name' => 'BOTSWANA', 'code' => '267'),
        'BY' => array('name' => 'BELARUS', 'code' => '375'),
        'BZ' => array('name' => 'BELIZE', 'code' => '501'),
        'CA' => array('name' => 'CANADA', 'code' => '1'),
        'CC' => array('name' => 'COCOS (KEELING) ISLANDS', 'code' => '61'),
        'CD' => array('name' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE', 'code' => '243'),
        'CF' => array('name' => 'CENTRAL AFRICAN REPUBLIC', 'code' => '236'),
        'CG' => array('name' => 'CONGO', 'code' => '242'),
        'CH' => array('name' => 'SWITZERLAND', 'code' => '41'),
        'CI' => array('name' => 'COTE D IVOIRE', 'code' => '225'),
        'CK' => array('name' => 'COOK ISLANDS', 'code' => '682'),
        'CL' => array('name' => 'CHILE', 'code' => '56'),
        'CM' => array('name' => 'CAMEROON', 'code' => '237'),
        'CN' => array('name' => 'CHINA', 'code' => '86'),
        'CO' => array('name' => 'COLOMBIA', 'code' => '57'),
        'CR' => array('name' => 'COSTA RICA', 'code' => '506'),
        'CU' => array('name' => 'CUBA', 'code' => '53'),
        'CV' => array('name' => 'CAPE VERDE', 'code' => '238'),
        'CX' => array('name' => 'CHRISTMAS ISLAND', 'code' => '61'),
        'CY' => array('name' => 'CYPRUS', 'code' => '357'),
        'CZ' => array('name' => 'CZECH REPUBLIC', 'code' => '420'),
        'DE' => array('name' => 'GERMANY', 'code' => '49'),
        'DJ' => array('name' => 'DJIBOUTI', 'code' => '253'),
        'DK' => array('name' => 'DENMARK', 'code' => '45'),
        'DM' => array('name' => 'DOMINICA', 'code' => '1767'),
        'DO' => array('name' => 'DOMINICAN REPUBLIC', 'code' => '1809'),
        'DZ' => array('name' => 'ALGERIA', 'code' => '213'),
        'EC' => array('name' => 'ECUADOR', 'code' => '593'),
        'EE' => array('name' => 'ESTONIA', 'code' => '372'),
        'EG' => array('name' => 'EGYPT', 'code' => '20'),
        'ER' => array('name' => 'ERITREA', 'code' => '291'),
        'ES' => array('name' => 'SPAIN', 'code' => '34'),
        'ET' => array('name' => 'ETHIOPIA', 'code' => '251'),
        'FI' => array('name' => 'FINLAND', 'code' => '358'),
        'FJ' => array('name' => 'FIJI', 'code' => '679'),
        'FK' => array('name' => 'FALKLAND ISLANDS (MALVINAS)', 'code' => '500'),
        'FM' => array('name' => 'MICRONESIA, FEDERATED STATES OF', 'code' => '691'),
        'FO' => array('name' => 'FAROE ISLANDS', 'code' => '298'),
        'FR' => array('name' => 'FRANCE', 'code' => '33'),
        'GA' => array('name' => 'GABON', 'code' => '241'),
        'GB' => array('name' => 'UNITED KINGDOM', 'code' => '44'),
        'GD' => array('name' => 'GRENADA', 'code' => '1473'),
        'GE' => array('name' => 'GEORGIA', 'code' => '995'),
        'GH' => array('name' => 'GHANA', 'code' => '233'),
        'GI' => array('name' => 'GIBRALTAR', 'code' => '350'),
        'GL' => array('name' => 'GREENLAND', 'code' => '299'),
        'GM' => array('name' => 'GAMBIA', 'code' => '220'),
        'GN' => array('name' => 'GUINEA', 'code' => '224'),
        'GQ' => array('name' => 'EQUATORIAL GUINEA', 'code' => '240'),
        'GR' => array('name' => 'GREECE', 'code' => '30'),
        'GT' => array('name' => 'GUATEMALA', 'code' => '502'),
        'GU' => array('name' => 'GUAM', 'code' => '1671'),
        'GW' => array('name' => 'GUINEA-BISSAU', 'code' => '245'),
        'GY' => array('name' => 'GUYANA', 'code' => '592'),
        'HK' => array('name' => 'HONG KONG', 'code' => '852'),
        'HN' => array('name' => 'HONDURAS', 'code' => '504'),
        'HR' => array('name' => 'CROATIA', 'code' => '385'),
        'HT' => array('name' => 'HAITI', 'code' => '509'),
        'HU' => array('name' => 'HUNGARY', 'code' => '36'),
        'ID' => array('name' => 'INDONESIA', 'code' => '62'),
        'IE' => array('name' => 'IRELAND', 'code' => '353'),
        'IL' => array('name' => 'ISRAEL', 'code' => '972'),
        'IM' => array('name' => 'ISLE OF MAN', 'code' => '44'),
        'IN' => array('name' => 'INDIA', 'code' => '91'),
        'IQ' => array('name' => 'IRAQ', 'code' => '964'),
        'IR' => array('name' => 'IRAN, ISLAMIC REPUBLIC OF', 'code' => '98'),
        'IS' => array('name' => 'ICELAND', 'code' => '354'),
        'IT' => array('name' => 'ITALY', 'code' => '39'),
        'JM' => array('name' => 'JAMAICA', 'code' => '1876'),
        'JO' => array('name' => 'JORDAN', 'code' => '962'),
        'JP' => array('name' => 'JAPAN', 'code' => '81'),
        'KE' => array('name' => 'KENYA', 'code' => '254'),
        'KG' => array('name' => 'KYRGYZSTAN', 'code' => '996'),
        'KH' => array('name' => 'CAMBODIA', 'code' => '855'),
        'KI' => array('name' => 'KIRIBATI', 'code' => '686'),
        'KM' => array('name' => 'COMOROS', 'code' => '269'),
        'KN' => array('name' => 'SAINT KITTS AND NEVIS', 'code' => '1869'),
        'KP' => array('name' => 'KOREA DEMOCRATIC PEOPLES REPUBLIC OF', 'code' => '850'),
        'KR' => array('name' => 'KOREA REPUBLIC OF', 'code' => '82'),
        'KW' => array('name' => 'KUWAIT', 'code' => '965'),
        'KY' => array('name' => 'CAYMAN ISLANDS', 'code' => '1345'),
        'KZ' => array('name' => 'KAZAKSTAN', 'code' => '7'),
        'LA' => array('name' => 'LAO PEOPLES DEMOCRATIC REPUBLIC', 'code' => '856'),
        'LB' => array('name' => 'LEBANON', 'code' => '961'),
        'LC' => array('name' => 'SAINT LUCIA', 'code' => '1758'),
        'LI' => array('name' => 'LIECHTENSTEIN', 'code' => '423'),
        'LK' => array('name' => 'SRI LANKA', 'code' => '94'),
        'LR' => array('name' => 'LIBERIA', 'code' => '231'),
        'LS' => array('name' => 'LESOTHO', 'code' => '266'),
        'LT' => array('name' => 'LITHUANIA', 'code' => '370'),
        'LU' => array('name' => 'LUXEMBOURG', 'code' => '352'),
        'LV' => array('name' => 'LATVIA', 'code' => '371'),
        'LY' => array('name' => 'LIBYAN ARAB JAMAHIRIYA', 'code' => '218'),
        'MA' => array('name' => 'MOROCCO', 'code' => '212'),
        'MC' => array('name' => 'MONACO', 'code' => '377'),
        'MD' => array('name' => 'MOLDOVA, REPUBLIC OF', 'code' => '373'),
        'ME' => array('name' => 'MONTENEGRO', 'code' => '382'),
        'MF' => array('name' => 'SAINT MARTIN', 'code' => '1599'),
        'MG' => array('name' => 'MADAGASCAR', 'code' => '261'),
        'MH' => array('name' => 'MARSHALL ISLANDS', 'code' => '692'),
        'MK' => array('name' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'code' => '389'),
        'ML' => array('name' => 'MALI', 'code' => '223'),
        'MM' => array('name' => 'MYANMAR', 'code' => '95'),
        'MN' => array('name' => 'MONGOLIA', 'code' => '976'),
        'MO' => array('name' => 'MACAU', 'code' => '853'),
        'MP' => array('name' => 'NORTHERN MARIANA ISLANDS', 'code' => '1670'),
        'MR' => array('name' => 'MAURITANIA', 'code' => '222'),
        'MS' => array('name' => 'MONTSERRAT', 'code' => '1664'),
        'MT' => array('name' => 'MALTA', 'code' => '356'),
        'MU' => array('name' => 'MAURITIUS', 'code' => '230'),
        'MV' => array('name' => 'MALDIVES', 'code' => '960'),
        'MW' => array('name' => 'MALAWI', 'code' => '265'),
        'MX' => array('name' => 'MEXICO', 'code' => '52'),
        'MY' => array('name' => 'MALAYSIA', 'code' => '60'),
        'MZ' => array('name' => 'MOZAMBIQUE', 'code' => '258'),
        'NA' => array('name' => 'NAMIBIA', 'code' => '264'),
        'NC' => array('name' => 'NEW CALEDONIA', 'code' => '687'),
        'NE' => array('name' => 'NIGER', 'code' => '227'),
        'NG' => array('name' => 'NIGERIA', 'code' => '234'),
        'NI' => array('name' => 'NICARAGUA', 'code' => '505'),
        'NL' => array('name' => 'NETHERLANDS', 'code' => '31'),
        'NO' => array('name' => 'NORWAY', 'code' => '47'),
        'NP' => array('name' => 'NEPAL', 'code' => '977'),
        'NR' => array('name' => 'NAURU', 'code' => '674'),
        'NU' => array('name' => 'NIUE', 'code' => '683'),
        'NZ' => array('name' => 'NEW ZEALAND', 'code' => '64'),
        'OM' => array('name' => 'OMAN', 'code' => '968'),
        'PA' => array('name' => 'PANAMA', 'code' => '507'),
        'PE' => array('name' => 'PERU', 'code' => '51'),
        'PF' => array('name' => 'FRENCH POLYNESIA', 'code' => '689'),
        'PG' => array('name' => 'PAPUA NEW GUINEA', 'code' => '675'),
        'PH' => array('name' => 'PHILIPPINES', 'code' => '63'),
        'PK' => array('name' => 'PAKISTAN', 'code' => '92'),
        'PL' => array('name' => 'POLAND', 'code' => '48'),
        'PM' => array('name' => 'SAINT PIERRE AND MIQUELON', 'code' => '508'),
        'PN' => array('name' => 'PITCAIRN', 'code' => '870'),
        'PR' => array('name' => 'PUERTO RICO', 'code' => '1'),
        'PT' => array('name' => 'PORTUGAL', 'code' => '351'),
        'PW' => array('name' => 'PALAU', 'code' => '680'),
        'PY' => array('name' => 'PARAGUAY', 'code' => '595'),
        'QA' => array('name' => 'QATAR', 'code' => '974'),
        'RO' => array('name' => 'ROMANIA', 'code' => '40'),
        'RS' => array('name' => 'SERBIA', 'code' => '381'),
        'RU' => array('name' => 'RUSSIAN FEDERATION', 'code' => '7'),
        'RW' => array('name' => 'RWANDA', 'code' => '250'),
        'SA' => array('name' => 'SAUDI ARABIA', 'code' => '966'),
        'SB' => array('name' => 'SOLOMON ISLANDS', 'code' => '677'),
        'SC' => array('name' => 'SEYCHELLES', 'code' => '248'),
        'SD' => array('name' => 'SUDAN', 'code' => '249'),
        'SE' => array('name' => 'SWEDEN', 'code' => '46'),
        'SG' => array('name' => 'SINGAPORE', 'code' => '65'),
        'SH' => array('name' => 'SAINT HELENA', 'code' => '290'),
        'SI' => array('name' => 'SLOVENIA', 'code' => '386'),
        'SK' => array('name' => 'SLOVAKIA', 'code' => '421'),
        'SL' => array('name' => 'SIERRA LEONE', 'code' => '232'),
        'SM' => array('name' => 'SAN MARINO', 'code' => '378'),
        'SN' => array('name' => 'SENEGAL', 'code' => '221'),
        'SO' => array('name' => 'SOMALIA', 'code' => '252'),
        'SR' => array('name' => 'SURINAME', 'code' => '597'),
        'ST' => array('name' => 'SAO TOME AND PRINCIPE', 'code' => '239'),
        'SV' => array('name' => 'EL SALVADOR', 'code' => '503'),
        'SY' => array('name' => 'SYRIAN ARAB REPUBLIC', 'code' => '963'),
        'SZ' => array('name' => 'SWAZILAND', 'code' => '268'),
        'TC' => array('name' => 'TURKS AND CAICOS ISLANDS', 'code' => '1649'),
        'TD' => array('name' => 'CHAD', 'code' => '235'),
        'TG' => array('name' => 'TOGO', 'code' => '228'),
        'TH' => array('name' => 'THAILAND', 'code' => '66'),
        'TJ' => array('name' => 'TAJIKISTAN', 'code' => '992'),
        'TK' => array('name' => 'TOKELAU', 'code' => '690'),
        'TL' => array('name' => 'TIMOR-LESTE', 'code' => '670'),
        'TM' => array('name' => 'TURKMENISTAN', 'code' => '993'),
        'TN' => array('name' => 'TUNISIA', 'code' => '216'),
        'TO' => array('name' => 'TONGA', 'code' => '676'),
        'TR' => array('name' => 'TURKEY', 'code' => '90'),
        'TT' => array('name' => 'TRINIDAD AND TOBAGO', 'code' => '1868'),
        'TV' => array('name' => 'TUVALU', 'code' => '688'),
        'TW' => array('name' => 'TAIWAN, PROVINCE OF CHINA', 'code' => '886'),
        'TZ' => array('name' => 'TANZANIA, UNITED REPUBLIC OF', 'code' => '255'),
        'UA' => array('name' => 'UKRAINE', 'code' => '380'),
        'UG' => array('name' => 'UGANDA', 'code' => '256'),
        'US' => array('name' => 'UNITED STATES', 'code' => '1'),
        'UY' => array('name' => 'URUGUAY', 'code' => '598'),
        'UZ' => array('name' => 'UZBEKISTAN', 'code' => '998'),
        'VA' => array('name' => 'HOLY SEE (VATICAN CITY STATE)', 'code' => '39'),
        'VC' => array('name' => 'SAINT VINCENT AND THE GRENADINES', 'code' => '1784'),
        'VE' => array('name' => 'VENEZUELA', 'code' => '58'),
        'VG' => array('name' => 'VIRGIN ISLANDS, BRITISH', 'code' => '1284'),
        'VI' => array('name' => 'VIRGIN ISLANDS, U.S.', 'code' => '1340'),
        'VN' => array('name' => 'VIET NAM', 'code' => '84'),
        'VU' => array('name' => 'VANUATU', 'code' => '678'),
        'WF' => array('name' => 'WALLIS AND FUTUNA', 'code' => '681'),
        'WS' => array('name' => 'SAMOA', 'code' => '685'),
        'XK' => array('name' => 'KOSOVO', 'code' => '381'),
        'YE' => array('name' => 'YEMEN', 'code' => '967'),
        'YT' => array('name' => 'MAYOTTE', 'code' => '262'),
        'ZA' => array('name' => 'SOUTH AFRICA', 'code' => '27'),
        'ZM' => array('name' => 'ZAMBIA', 'code' => '260'),
        'ZW' => array('name' => 'ZIMBABWE', 'code' => '263')
    );
    foreach ($countryArray as $key => $value) {
        if ($key == $code) {
            $country = ucfirst($value['name']);
            break;
        }
    }
    return $country;
}

function IMSIPTV_get_admin() {
    $admin = Capsule::table('tbladmins')->where('roleid', 1)->first();
    return $admin->id;
}

function IMSIPTV_doCheckLicense($license) {
    if ($license['license']) {
        $localkey = '';
        $result = IMSIPTV_check_license($license, $localkey);
        $result['status'] = $result['status'];
    } else {
        $result['status'] = 'License key not found or Not correct';
    }

    return $result;
}

function IMSIPTV_check_license($licensekey, $localkey = '') {

    // -----------------------------------
    //  -- Configuration Values --
    // -----------------------------------
    // Enter the url to your WHMCS installation here
    $whmcsurl = 'http://whcms.fakebrandtv.com/';
    // Must match what is specified in the MD5 Hash Verification field
    // of the licensing product that will be used with this check.
    $licensing_secret_key = '@#fakebrandtvIMSIPTV$%^';
    // The number of days to wait between performing remote license checks
    $localkeydays = 5;
    // The number of days to allow failover for after local key expiry
    $allowcheckfaildays = 7;

    // -----------------------------------
    //  -- Do not edit below this line --
    // -----------------------------------

    $check_token = time() . md5(mt_rand(100000000, mt_getrandmax()) . $licensekey);
    $checkdate = date("Ymd");
    $domain = $_SERVER['SERVER_NAME'];
    $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
    $dirpath = dirname(__FILE__);
    $verifyfilepath = 'modules/servers/licensing/verify.php';
    $localkeyvalid = false;
    if ($localkey) {
        $localkey = str_replace("\n", '', $localkey); # Remove the line breaks
        $localdata = substr($localkey, 0, strlen($localkey) - 32); # Extract License Data
        $md5hash = substr($localkey, strlen($localkey) - 32); # Extract MD5 Hash
        if ($md5hash == md5($localdata . $licensing_secret_key)) {
            $localdata = strrev($localdata); # Reverse the string
            $md5hash = substr($localdata, 0, 32); # Extract MD5 Hash
            $localdata = substr($localdata, 32); # Extract License Data
            $localdata = base64_decode($localdata);
            $localkeyresults = json_decode($localdata, true);
            $originalcheckdate = $localkeyresults['checkdate'];
            if ($md5hash == md5($originalcheckdate . $licensing_secret_key)) {
                $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $localkeydays, date("Y")));
                if ($originalcheckdate > $localexpiry) {
                    $localkeyvalid = true;
                    $results = $localkeyresults;
                    $validdomains = explode(',', $results['validdomain']);
                    if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                        $localkeyvalid = false;
                        $localkeyresults['status'] = "Invalid";
                        $results = array();
                    }
                    $validips = explode(',', $results['validip']);
                    if (!in_array($usersip, $validips)) {
                        $localkeyvalid = false;
                        $localkeyresults['status'] = "Invalid";
                        $results = array();
                    }
                    $validdirs = explode(',', $results['validdirectory']);
                    if (!in_array($dirpath, $validdirs)) {
                        $localkeyvalid = false;
                        $localkeyresults['status'] = "Invalid";
                        $results = array();
                    }
                }
            }
        }
    }
    if (!$localkeyvalid) {
        $responseCode = 0;
        $postfields = array(
            'licensekey' => $licensekey,
            'domain' => $domain,
            'ip' => $usersip,
            'dir' => $dirpath,
        );
        if ($check_token)
            $postfields['check_token'] = $check_token;
        $query_string = '';
        foreach ($postfields AS $k => $v) {
            $query_string .= $k . '=' . urlencode($v) . '&';
        }
        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $responseCodePattern = '/^HTTP\/\d+\.\d+\s+(\d+)/';
            $fp = @fsockopen($whmcsurl, 80, $errno, $errstr, 5);
            if ($fp) {
                $newlinefeed = "\r\n";
                $header = "POST " . $whmcsurl . $verifyfilepath . " HTTP/1.0" . $newlinefeed;
                $header .= "Host: " . $whmcsurl . $newlinefeed;
                $header .= "Content-type: application/x-www-form-urlencoded" . $newlinefeed;
                $header .= "Content-length: " . @strlen($query_string) . $newlinefeed;
                $header .= "Connection: close" . $newlinefeed . $newlinefeed;
                $header .= $query_string;
                $data = $line = '';
                @stream_set_timeout($fp, 20);
                @fputs($fp, $header);
                $status = @socket_get_status($fp);
                while (!@feof($fp) && $status) {
                    $line = @fgets($fp, 1024);
                    $patternMatches = array();
                    if (!$responseCode && preg_match($responseCodePattern, trim($line), $patternMatches)
                    ) {
                        $responseCode = (empty($patternMatches[1])) ? 0 : $patternMatches[1];
                    }
                    $data .= $line;
                    $status = @socket_get_status($fp);
                }
                @fclose($fp);
            }
        }
        if ($responseCode != 200) {
            $localexpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($localkeydays + $allowcheckfaildays), date("Y")));
            if ($originalcheckdate > $localexpiry) {
                $results = $localkeyresults;
            } else {
                $results = array();
                $results['status'] = "Invalid";
                $results['description'] = "Remote Check Failed";
                return $results;
            }
        } else {
            preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
            $results = array();
            foreach ($matches[1] AS $k => $v) {
                $results[$v] = $matches[2][$k];
            }
        }
        if (!is_array($results)) {
            die("Invalid License Server Response");
        }
        if ($results['md5hash']) {
            if ($results['md5hash'] != md5($licensing_secret_key . $check_token)) {
                $results['status'] = "Invalid";
                $results['description'] = "MD5 Checksum Verification Failed";
                return $results;
            }
        }
        if ($results['status'] == "Active") {
            $results['checkdate'] = $checkdate;
            $data_encoded = json_encode($results);
            $data_encoded = base64_encode($data_encoded);
            $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
            $data_encoded = strrev($data_encoded);
            $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
            $data_encoded = wordwrap($data_encoded, 80, "\n", true);
            $results['localkey'] = $data_encoded;
        }
        $results['remotecheck'] = true;
    }
    unset($postfields, $data, $matches, $whmcsurl, $licensing_secret_key, $checkdate, $usersip, $localkeydays, $allowcheckfaildays, $md5hash);
    return $results;
}