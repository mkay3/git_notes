<?php

namespace Biz2Credit\Hdfc\Libraries;

use Auth;
use Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
    use Crypt;
    use \Firebase\JWT\JWT;
    use Illuminate\Filesystem\Filesystem;

class Builder
{

    /**
     * Environment
     *
     * @var type string
     */
    public $env;
protected $file;
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->env = App::environment();
        $this->file = new Filesystem();
    }

    /**
     * Build HDFC Validate Partner Request
     *
     * @param array $params
     * @return string
     */
    public function buildHdfcValidatePartnerRequest($params)
    {
        $data = ['ToValidatePartner' => [
            "deviceId" => Config::get("hdfc.$this->env.device_id"),
            "mobileNumber" => "8928150960",
            "RequestTime" => Helpers::getDateByFormat(Helpers::getCurrentDateTime(), 'Y-m-d H:i:s', 'Y/m/d H:i:s A'),
            "UniqueKey" => Config::get("hdfc.$this->env.unique_key"),
            "Filler1" => "Website",
            "Filler2" => "",
            "Filler3" => "",
            "Filler4" => $this->generateAlphaNumericStr(),
            "Filler5" => "",
            "Version" => "",
            "UserName" => Config::get("hdfc.$this->env.partner_username"),
            "Password" => Config::get("hdfc.$this->env.partner_password")
        ]];
        return json_encode($data);
    }

    /**
     * Prepare HDFC Api Transaction Log data
     *
     * @param  $requestData
     * @param  $responseData
     * @param array $logDataArr
     *
     * @return array
     */
    public function prepareTransactionLogDataToSave($requestData, $responseData, $logDataArr)
    {
        $arrTransaction = [];
        $arrTransaction['req_file_name'] = $requestData;
        $arrTransaction['res_file_name'] = $responseData;
        $arrTransaction['match_id'] = $logDataArr['match_id'];
        $arrTransaction['lender_id'] = config('b2c_common.LENDER_ID.HDFC');
        $arrTransaction['method_name'] = $logDataArr['method_name'];
        $arrTransaction['prod_type_id'] = $logDataArr['prod_type_id'];
        $arrTransaction['trans_id'] = $logDataArr['trans_id'];
        $arrTransaction['status'] = $logDataArr['status'];
        return $arrTransaction;
    }

    /**
     * Prepare HDFC Transaction data
     *
     * @param xml $logId
     * @param int $appId
     * @param int $matchId
     * @param int $prod_type_id
     *
     * @return array
     */
    public function prepareTransactionDataToSave($logId, $appId, $matchId, $prod_type_id)
    {
        $arrTransaction = [];
        $arrTransaction['api_log_id'] = $logId;
        $arrTransaction['app_id'] = $appId;
        $arrTransaction['match_id'] = $matchId;
        $arrTransaction['lender_id'] = config('b2c_common.LENDER_ID.HDFC');
        $arrTransaction['product_type_id'] = $prod_type_id;

        return $arrTransaction;
    }

    /**
     * Prepare HDFC Transaction Offer data
     *
     * @param array $xmlArr
     * @param int $matchId
     * @param int $matchSubmitID
     *
     * @return array
     */
    public function prepareTransactionOfferDataToSave($xmlArr, $matchId, $matchSubmitID)
    {
        $arrTransaction = [];
        $arrTransaction['app_submit_id'] = $matchSubmitID;
        $arrTransaction['match_id'] = $matchId;
        $arrTransaction['loan_amount'] = $xmlArr['Application']['Result']['ExpectedLoanAmount'];
        $arrTransaction['processing_fee'] = !empty((float) $xmlArr['Application']['Result']['OfferRate']) && !empty((float) $xmlArr['Application']['Result']['OfferEMI'])?$xmlArr['Application']['Result']['OfferProcessingfee']:$xmlArr['Application']['Result']['ProcessingFee'];
        $arrTransaction['tenure'] = $xmlArr['Application']['Result']['ExpectedTenure'];
        $arrTransaction['roi'] = !empty((float) $xmlArr['Application']['Result']['OfferRate'])?$xmlArr['Application']['Result']['OfferRate']:$xmlArr['Application']['Result']['Rate'];
        $arrTransaction['emi'] = !empty((float) $xmlArr['Application']['Result']['OfferEMI'])?$xmlArr['Application']['Result']['OfferEMI']:$xmlArr['Application']['Result']['Emi'];
        $arrTransaction['lender_reference_id'] = Null;
        $arrTransaction['is_active'] = 1;

        return $arrTransaction;
    }
    
    /**
     * Generate 20 chars alpha numeric string
     *
     *
     * @return string
     */
    public function generateAlphaNumericStr()
    {
        return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 20);
    }
    
    /**
     * Build HDFC Apply For Loan Request
     *
     * @param array $params, string $encryKey
     * @return string
     */
    public function buildApplyForLoanRequest($params, $encryKey)
    {
        dd($params);
        $data = ['FintechRequest' => [
            "security_key" => "458738aaf8223c4d3121966e20dbff7d",
            "business_id" => "50082",
            "organization_id" => "10000",
            "channel" => "BA",
            "preferred_language_only" => "T",
            "ignore_source_restriction" => "T",
            "bypass_open_n_click_check" => "T",
            "source" => "Anderomeda",
            "specified_source_only" => "T",
            "content_count" => "0",
            "personalization_required" => "T",
            "Transaction_refno" => "AI53455",
            "Mobile_no" => "9022102122",
            "Partner_name" => "ASH",
            "Source_name" => "HDFC",
            "Identifier_Name" => "AADHAR_NO",
            "Identifier_Value" => "72325821456",
            "Product_Name" => "PL",
            "Offer_Available" => "Y",
            "Existing_Customer" => "Y",
            "Source_ID" => "1234",
            "Salutation" => "",
            "First_Name" => "Sachin",
            "Last_Name" => "Tandle",
            "First_Name_Edit" => "Sachin",
            "Last_Name_Edit" => "Tandle",
            "Applicant_Type" => "P",
            "Gender" => "M",
            "Gender_Edit" => "M",
            "Date_Of_Birth" => "1982-02-06",
            "Date_Of_Birth_Edit" => "1982-02-06",
            "Age" => "",
            "Constitution" => "16",
            "Product"=> "P",
            "asset_type" => "",
            "Asset_Category" => "",
            "Asset_Make" => "",
            "Asset_Model" => "",
            "Asset_Cost" => "",
            "Asset_Manufacturer" => "",
            "Margin_Money" => "",
            "Loan_Amount" => "1000000",
            "Tenure_in_Months" => "60",
            "EMI" => "0",
            "DSA" => "",
            "CONSENT_TO_CALL" => "NA",
            "Educational_Qualification" => "GRAD",
            "Professional_Qualification" => "",
            "No_of_Dependent" => "0",
            "PAN_AC_No" => "QWERT1234Y",
            "Driving_License_Number" => "",
            "Sales_Promotion" => "XXXXX",
            "Scheme" => "4",
            "Priority" => "CCPA",
            "Address_Type_Resi" => "CURRES",
            "Address1_Resi" => "ASCRA TECHNOLOGIES",
            "Address2_Resi" => "515 RUPA SOLITAIRE MILLENNIUM",
            "Address3_Resi" => "HAPE",
            "Landmark_Resi" => "",
            "City_Resi" => "510",
            "Pin_Code_Resi" => "400710",
            "State_Resi" => "1",
            "Country_Resi" => "1",
            "STD_Code_Resi" => "",
            "Phone1_Resi" => "",
            "Phone1_Work" => "PHW1",
            "Mobile1_Resi" => "9890906797",
            "Email_Resi" => "sachin.t14 @gmail.com",
            "Address_Type_Resi_Edit" => "Y",
            "Address1_Resi_Edit" => "N",
            "Address2_Resi_Edit" => "N",
            "Address3_Resi_Edit" => "Y",
            "Landmark_Resi_Edit" => "N",
            "City_Resi_Edit" => "510",
            "Pin_Code_Resi_Edit" => "400710",
            "State_Resi_Edit" => "1",
            "Country_Resi_Edit" => "1",
            "STD_Code_Resi_Edit" => "",
            "Phone1_Resi_Edit" => "",
            "Mobile1_Resi_Edit" => "9890906797",
            "Mobile2_Resi_Edit" => "",
            "Email_Resi_Edit" => "sachin.t14 @gmail.com",
            "Year_at_Current_Address" => "2012",
            "Mailing_Address_Resi" => "Y",
            "Year_at_City" => "2012",
            "Employer_Name" => "29636",
            "Employer_Name_other" => "",
            "Address_Type_Work" => "OFFICE",
            "Address1_Work" => "36 - a",
            "Address2_Work" => "big apple",
            "Landmark_Work" => "",
            "City_Work" => "510",
            "Pin_Code_Work" => "400710",
            "State_Work" => "1",
            "Country_Work" => "1",
            "STD_Code_Work" => "",
            "Mailing_Address_Work" => "N",
            "indv_corp" => "I",
            "product_category" => "PERSONAL",
            "Sales_Executive_code" => "",
            "Start_Date_and_Time" => "",
            "Final_Submit_Date_and_Time" => "",
            "Aadhar" => "",
            "Voter_Id" => "",
            "Driving_License" => "",
            "Passport_no" => "",
            "Preapproved_Amount" => "25000",
            "Monthly_take_home_Salary" => "",
            "Proposed_EMI" => "23000",
            "Profession" => "",
            "CustomerID" => "",
            "AccountNo" => "8.62E+11",
            "cibil_chk_done" => "N",
            "existing_customer" => "N",
            "Los_AgreementID" => "",
            "Ref_No" => "",
            "flag" => "",
            "request_time" => "2018/07/25 11:24:28 AM",
            "deviceId" => "FinTech",
            "UniqueKey" => "HDFCMOBAPPU9Z60M5RMX",
            "Branch" => "",
            "Bank_Name" => "",
            "Bank_Branch" => "",
            "Account_Type" => "",
            "Account_No" => "12435546567867873465",
            "Years_held" => "",
            "residence_type_dap" => "OWNED",
            "total_work_experience_dap" => "",
            "applicant_description_dap" => "",
            "previous_year_profit_dap" => "",
            "latest_year_profit_dap" => "",
            "latest_year_depreciation_dap" => "",
            "latest_itr_audited_dap" => "",
            "afn_no" => "",
            "RM_code" => "",
            "SE_code" => "",
            "Branch_code" => "",
            "year_at_city_c" => "",
            "Address3_Work" => "",
            "LGCode" => "",
            "employment_type" => "",
            "Authorize" => "",
            "PF" => "",
            "Promocode" => "",
            "IRR" => "",
            "BTFlag" => "",
            "campaign_name" => "",
            "kyc" => "",
            "WinId_EmpId" => "",
            "Type" => "",
            "Lead_Id" => "",
            "offer_details" => "",
            "SalAcc" => "",
            "Group1_Updated_flag" => "N",
            "Group2_Updated_flag" => "Y",
            "Group3_Updated_flag" => "Y",
            "Group4_Updated_flag" => "Y",
            "soaStandard" => [       
            "service_user" => "",
            "service_password" => "",
            "consumer_name" => "",
            "unique_id" => "",
            "time_stamp" => ""
            ],    
            "soaFillers" => [
            "filler1" => $encryKey,
            "filler2" => "",
            "filler3" => "",
            "filler4" => "aaaaaaaaaaaaaaaa0003",
            "filler5" => ""
            ]
        ]];
        return json_encode($data);
    }


    public function OfferAvailabilityRequest(){
        $key = $this->generateRandomString(32);
        $params = [];
        $params['RequestSignatureEncryptedValue'] = $this->getRequestSignatureEncryptedValue($key,$iv);
        $params['SymmetricKeyEncryptedValue'] = $this->getSymmetricKeyEncryptedValue($key);
        $params['Scope'] = "Andromeda";
        $params['TransactionId'] = "";
        $params['iv'] = base64_encode($iv);
        return json_encode($params);
       
    }

    public function getRequestSignatureEncryptedValue($randomkey,$iv){
        $payload = '{  
            "FintechRequest": {
                "Transaction_refno": "123456",
                "Mobile_no": "9022102122",
                "Partner_name": "",
                "Source-name": "",
                "Identifier_Name":"PAN_NO",
                "Identifier_Value": "PQ_WITH_KYC",
                "Product_name": "CC",
                "security_key": "458738aaf8223c4d3121966e20dbff7d",
                "business_id": "50082",
                "organization_id": "10000", 
                "session_key": "",
                "partner_code": "",
                "partner_tracking_id": "",
                "channel": "BA",
                "customer_key": "",
                "customer_key2": "",
                "customer_code": "",
                "preferred_language": "",
                "preferred_language_only": "T",
                "ignore_source_restriction": "T",
                "bypass_open_n_click_check": "T",
                "source": "OFR",
                "flow_point": "",
                "specified_source_only": "T",
                "additional_filter": "",
                "content_count": "0",
                "personalization_required": "T",
                "soaStandard": {
                    "service_user": "",
                    "service_password": "",
                    "consumer_name": "",
                    "unique_id": "",
                    "time_stamp": ""
                },
                "soaFillers": {
                    "filler1": "",
                    "filler2": "",
                    "filler3": "",
                    "filler4": "",
                    "filler5": ""
                } 
            }
        }';
        
        $payload = json_decode($payload, true);
        //$private_key = openssl_get_publickey(file_get_contents(__DIR__."/publickey.cer"));
        $private_key = $this->getCertificatePrivateKey();
       
        //digitally sign the request using RS256
        $jwt = JWT::encode($payload, $private_key, "RS256");
      
        //Base64 encode the result
        $jwt_encoded = base64_encode($jwt); //may /may not required
     
        //Get 32 bytes random string
        $key = $randomkey;
        
        $encrypted_digital_signature = $this->encryptSignature($jwt,$key,$iv);
        return $encrypted_digital_signature;
    }

    public function generateRandomString($length = 32){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function intvector(){
        $cipher="AES-256-CBC";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        return $iv;    
    }

    public function encryptSignature($jwt,$key,$iv){
        //encrypt the signature using random string,pkcs5 
            $cipher="AES-256-CBC";
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $jwt = $iv.$jwt;
            $req_digital_signature = openssl_encrypt($jwt,'AES-256-CBC',$key,false,$iv);
            return $req_digital_signature;
    }

    public function getSymmetricKeyEncryptedValue($randomkey){
        //Get 32 bytes random string
        $key = $randomkey;
        //$key_encoded = base64_encode($key);  // not required 
        $hdfc_public_key = $this->getCertificatePublicKey();
        $encrypted_key = $this->encryptKey($key,$hdfc_public_key);
        
        // base64 encode the encrypted key
        $encrypted_key=base64_encode($encrypted_key);
        return $encrypted_key;
    }

    public function encryptKey($key,$hdfc_public_key){
        //asymmetric key encryption pkcs1 padding
        $bool = openssl_public_encrypt($key,$crypted,$hdfc_public_key,OPENSSL_PKCS1_PADDING);
        return $crypted;
    }

    public function getCertificatePrivateKey(){
    	$private_key = file_get_contents('/etc/httpd/ssl/andromeda-dev/certificate.key');
    	return $private_key;
    }

    public function getCertificatePublicKey(){
        $hdfc_certificate = openssl_pkey_get_public(file_get_contents(__DIR__."/publickey.cer")); 
        $hdfc_certificate_details = openssl_pkey_get_details($hdfc_certificate);
        return $hdfc_certificate_details['key'];
    }

    //RESPONSE RELATED FUNCTIONS
    public function OfferAvailabilityResponse($responseData,$iv){

    	$data = [];
    	$data['GWSymmetricKeyDecryptedValue'] = $this->DecryptKey($responseData['GWSymmetricKeyEncryptedValue']);
    	$data['ResponseSignatureDecryptedValue'] = $this->DecryptSignature($responseData['ResponseSignatureEncryptedValue'],$data['GWSymmetricKeyDecryptedValue'],$iv);
    	$data['Scope'] = $responseData['Scope'];
    	$data['TransactionId'] = $responseData['TransactionId'];
        //dd($data);
       // $data['ResponseSignatureDecryptedValue'] = "eyJ0eXAiOiJKV1QiLA0KICJhbGciOiJSUzI1NiJ9.eyJGaW50ZWNoUmVzcG9uc2UiOiB7CiAgICAiQ1BfRXJyb3JEZXNjIjogIkVycm9yIChDb2RlOiAyNzI0MykgOiBDdXN0b21lciBLZXkgY291bGQgbm90IGJlIHJldHJpZXZlZCBiYXNlZCBvbiBjdXN0b21lciBjb2RlIiwKICAgICJJZGVudGlmaWVyX1ZhbHVlIjogIlBRX1dJVEhfS1lDIiwKICAgICJFeGlzdGluZ19DdXN0b21lciI6ICIiLAogICAgIkNQX0Vycm9yQ29kZSI6ICJFcnJvciAoQ29kZTogMjcyNDMpIDogQ3VzdG9tZXIgS2V5IGNvdWxkIG5vdCBiZSByZXRyaWV2ZWQgYmFzZWQgb24gY3VzdG9tZXIgY29kZSA5MDIyMTAyMTIyIiwKICAgICJTb3VyY2UtbmFtZSI6ICIiLAogICAgIk9GRkVSX0FWQUlMQUJMRSI6ICIiLAogICAgIlRyYW5zYWN0aW9uX3JlZm5vIjogIjEyMzQ1NiIsCiAgICAiUGFydG5lcl9uYW1lIjogIiIsCiAgICAiQmFua1RyYW5zYWN0aW9uX1JlZm5vIjogIiIsCiAgICAiTW9iaWxlX25vIjogIjkwMjIxMDIxMjIiLAogICAgIklkZW50aWZpZXJfTmFtZSI6ICJQQU5fTk8iLAogICAgIlByb2R1Y3RfbmFtZSI6ICJDQyIsCiAgICAiQ0lGX0VSUk9SREVTQyI6ICIiLAogICAgIkNJRl9FcnJvckNvZGUiOiAiIiwKICAgICJzb2FGaWxsZXJzIjogewogICAgICAgICJmaWxsZXI1IjogIiIsCiAgICAgICAgImZpbGxlcjQiOiAiIiwKICAgICAgICAiZmlsbGVyMSI6ICIiLAogICAgICAgICJmaWxsZXIzIjogIiIsCiAgICAgICAgImZpbGxlcjIiOiAiIgogICAgfQp9fQ.Cqo1avzxwoFkCI9tJ-gXFz8nl3l3c44Rd4UVs0XBdAtdIWL-nw1b8SJLzuy-kFA3q-WJPmzvRR0Fje-MIIEQTPrh7P79C85fKkED8dUBL6ASg7YePMFsjtPcrizv_nlL3ApkvBwZ1671SnISNvLgesmlndyEWBlY-kn0JpzpOt1rEXU0wEuLcLlY0YfpSEOFdSEiKJqwvKZyLxjUo8j8qbQNVylqM1f0kOd1kkwYFRh32BhOw18LoOF6E5rK0RqGUBIhEmvDdhDjmogc9lvwfOj7cabPARac2mzqxkx9KaQRto6sBBFkNrf-aqArEFWlnPM1dmhnuAseZOvXbBOcpw";

    	//next (verify signature)
        $data['ResponseSignatureDecryptedValue'] = filter_var($data['ResponseSignatureDecryptedValue'], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH); //mmm
    	$public_key = $this->getCertificatePublicKey();
    	$request = JWT::decode($data['ResponseSignatureDecryptedValue'], $public_key, array('RS256'));
    	dd($request); 
    }

    public function DecryptSignature($SignatureEncryptedValue,$decrypted_key,$iv)
    {   
		$cipher="AES-256-CBC";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        //echo "<br><br><br><br>";
        //$decrypted_key = base64_decode($decrypted_key); // not possible .
        //echo "test<br><br><br>";
        echo "ddd  <br><br><br>";
        //$iv = base64_decode($iv);
        $decrypted_digital_signature = openssl_decrypt($SignatureEncryptedValue,'AES-256-CBC',$decrypted_key,false,$iv);
        dd($decrypted_digital_signature); 
         $decrypted_digital_signature = filter_var($decrypted_digital_signature, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);  
       
        //$decrypted_digital_signature = base64_decode($decrypted_digital_signature); //must //mproblem
        return $decrypted_digital_signature;
    }

    //right
    public function DecryptKey($encrypted_key){
    	$hdfc_private_key = $this->getCertificatePrivateKey();
        $encrypted_key = base64_decode($encrypted_key); //must //maybe problem
        $bool =openssl_private_decrypt($encrypted_key,$decrypted,$hdfc_private_key,OPENSSL_PKCS1_PADDING);
        //dd($decrypted);  // i.e d24fd9e4417c48998561ccece8e8b09e
        return $decrypted;
    }


	


}
