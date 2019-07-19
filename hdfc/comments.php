<?php

namespace Biz2Credit\HdfcBank\Libraries;

use Auth;
use Crypt;
use Helpers;
use Carbon\Carbon;
use \Firebase\JWT\JWT;
use Illuminate\Support\Facades\App;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Biz2Credit\HdfcBank\Libraries\RestClient;

class Builder
{

    /**
     * Environment
     *
     * @var type string
     */
    public $env;
    
    /**
     * Rest Client
     *
     * @var \Biz2Credit\HdfcBank\Libraries\RestClient
     */
    private $httpClient;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->env = App::environment();
        $this->httpClient = new RestClient();
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
            "deviceId" => Config::get("hdfcbank.$this->env.device_id"),
            "mobileNumber" => "8928150960",
            "RequestTime" => Helpers::getDateByFormat(Helpers::getCurrentDateTime(), 'Y-m-d H:i:s', 'Y/m/d h:i:s A'),
            "UniqueKey" => 'HDFCMOBAPPCDIRJ6BWF2',
            "Filler1" => "Website",
            "Filler2" => "",
            "Filler3" => "",
            "Filler4" => $this->generateAlphaNumericStr(),
            "Filler5" => "",
            "Version" => "",
            "UserName" => Config::get("hdfcbank.$this->env.partner_username"),
            "Password" => Config::get("hdfcbank.$this->env.partner_password")
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
        return strtoupper(substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 20));
    }
    
    /**
     * Build HDFC Apply For Loan Request
     *
     * @param array $params, string $encryKey
     * @return string
     */
    public function buildApplyForLoanRequest($params, $encryKey)
    {
        $empType = Helpers::getEmploymentTypeNameByLender(config('b2c_common.LENDER_ID.HDFC'), $params['employment_type_id']);
        $lenderGenderName = Helpers::getLenderGenderNameByLender(config('b2c_common.LENDER_ID.HDFC'), $params['gender']);
        $lenderCityName = Helpers::getCityByLender(config('b2c_common.LENDER_ID.HDFC'), $params['city_id'], $params['pincode']);
        $lenderStateCode = Helpers::getStateByLender(config('b2c_common.LENDER_ID.HDFC'), $params['state_id']);
        $lenderOfficeCityName = Helpers::getCityByLender(config('b2c_common.LENDER_ID.HDFC'), $params['office_city_id'], $params['office_pincode']);
        $lenderOfficeStateCode = Helpers::getStateByLender(config('b2c_common.LENDER_ID.HDFC'), $params['office_state_id']);
        $qualification = Helpers::getQualificationByLender(config('b2c_common.LENDER_ID.HDFC'), $params['qualification_id']);
        $residenceType = Helpers::getResidenceTypeByLender(config('b2c_common.LENDER_ID.HDFC'), $params['residense_type']);
        $salutationName = Helpers::getLenderSalutationNameByLender(config('b2c_common.LENDER_ID.HDFC'), $params['salutation_id']); 
        $empType = Helpers::getEmploymentTypeNameByLender(config('b2c_common.LENDER_ID.HDFC'), $params['employment_type_id']);
        if(!empty($params['company_id'])){
                    $companyArrData = Helpers::getCompanyByLenderId($params['company_id'], config('b2c_common.LENDER_ID.HDFC'));                          
                    if($companyArrData != false){
                       $params['company_name'] = $companyArrData['lender_company_name'];                          
                       $params['company_code'] = $companyArrData['company_code']; 
                    }
                }
        $encypAmountStr = $encryKey.$params['loan_amt'].$encryKey;
        $encypTenureStr = $encryKey.($params['preferred_loan_tenure']*12).$encryKey;
        $encypProposedEmiStr = $encryKey.'4000'.$encryKey;
        $encypPfStr = $encryKey.'1000'.$encryKey;
        $encypIrrStr = $encryKey.'15'.$encryKey;
        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $encypAmountStr = $this->httpClient->restClientPost(Config::get("hdfcbank.$this->env.hdfc_encryp_url1"), '{"request_text":"'.$encypAmountStr.'"}', $headers);
        $encypTenureStr = $this->httpClient->restClientPost(Config::get("hdfcbank.$this->env.hdfc_encryp_url1"), '{"request_text":"'.$encypTenureStr.'"}', $headers);
        $encypProposedEmiStr = $this->httpClient->restClientPost(Config::get("hdfcbank.$this->env.hdfc_encryp_url1"), '{"request_text":"'.$encypProposedEmiStr.'"}', $headers);
        $encypPfStr = $this->httpClient->restClientPost(Config::get("hdfcbank.$this->env.hdfc_encryp_url1"), '{"request_text":"'.$encypPfStr.'"}', $headers);
        $encypIrrStr = $this->httpClient->restClientPost(Config::get("hdfcbank.$this->env.hdfc_encryp_url1"), '{"request_text":"'.$encypIrrStr.'"}', $headers);
        $curResidanceSince = Helpers::getDateByFormat(Helpers::getDateByYearMonth($params['curr_residense_year'], $params['curr_residense_month']), 'd-m-Y', 'Y');
        $curCitySince = Helpers::getDateByFormat(Helpers::getDateByYearMonth($params['residing_in_city_year'], $params['residing_in_city_month']), 'd-m-Y', 'Y');
        $data = ['applyLoan' => [
            "Salutation" => $salutationName['lender_code'],
            "First_Name" => $params['first_name'],
            "Last_Name" => $params['last_name'],
            "First_Name_Edit" => $params['first_name'],
            "Last_Name_Edit" => $params['last_name'],
            "Applicant_Type" => "P",
            "Gender" => $lenderGenderName['lender_code'],
            "Gender_Edit" => $lenderGenderName['lender_code'],
            "Date_Of_Birth" => $params['dob'],
            "Date_Of_Birth_Edit" => $params['dob'],
            "Age" => "",
            "Constitution" => "16",
            "Product" => "P",
            "asset_type" => "",
            "Asset_Category" => "",
            "Asset_Make" => "",
            "Asset_Model" => "",
            "Asset_Cost" => "",
            "Asset_Manufacturer" => "",
            "Margin_Money" => "",
            "Loan_Amount" => $encypAmountStr,
            "Tenure_in_Months" => $encypTenureStr,
            "EMI" => $params['emi'],
            "DSA" => "",
            "CONSENT_TO_CALL" => "NA", 
            "Educational_Qualification" => $qualification['lender_code'],
            "Professional_Qualification" => "",
            "No_of_Dependent" => "0",
            "PAN_AC_No" => $params['pan_no'],
            "Driving_License_Number" => "",
            "Sales_Promotion" => "XXXXX",
            "Scheme" => "4",
            "Priority" => "CCPA",
            "Address_Type_Resi" => "CURRES",
            "Address1_Resi" => $params['address_one'],
            "Address2_Resi" => !empty($params['address_two']) ? $params['address_two'] : $params['address_one'],
            "Address3_Resi" => "HAPE",
            "Landmark_Resi" => !empty($params['landmark']) ? $params['landmark'] : "",
            "City_Resi" => $lenderCityName['lender_code'],
            "Pin_Code_Resi" => $params['pincode'],
            "State_Resi" => $lenderStateCode['lender_code'],
            "Country_Resi" => "1",
            "STD_Code_Resi" => "",
            "Phone1_Resi" => "",
            "Mobile1_Resi" => $params['mobile_no'],
            "Email_Resi" => $params['email'],
            "Address_Type_Resi_Edit" => "CURRES",
            "Address1_Resi_Edit" => $params['address_one'],
            "Address2_Resi_Edit" => !empty($params['address_two']) ? $params['address_two'] : $params['address_one'],
            "Address3_Resi_Edit" => "HAPE",
            "Landmark_Resi_Edit" => !empty($params['landmark']) ? $params['landmark'] : "",
            "City_Resi_Edit" => $lenderCityName['lender_code'],
            "Pin_Code_Resi_Edit" => $params['pincode'],
            "State_Resi_Edit" => $lenderStateCode['lender_code'],
            "Country_Resi_Edit" => "1",
            "STD_Code_Resi_Edit" => "",
            "Phone1_Resi_Edit" => "",
            "Mobile1_Resi_Edit" => $params['mobile_no'],
            "Mobile2_Resi_Edit" => "",
            "Email_Resi_Edit" => $params['email'],
            "Year_at_Current_Address" => $curResidanceSince,
            "Mailing_Address_Resi" => "Y",
            "Year_at_City" => $curCitySince,
            "Employer_Name" => "24352",
            "Employer_Name_other" => "",
            "Address_Type_Work" => "OFFICE",
            "Address1_Work" => $params['office_address_one'],
            "Address2_Work" => !empty($params['office_address_two']) ? $params['office_address_two'] : $params['office_address_one'],
            "Landmark_Work" => !empty($params['office_landmark']) ? $params['office_landmark'] : "",
            "City_Work" => $lenderOfficeCityName['lender_code'],
            "Pin_Code_Work" => $params['office_pincode'],
            "State_Work" => $lenderOfficeStateCode['lender_code'],
            "Country_Work" => "1",
            "STD_Code_Work" => "",
            "Phone1_Work" => "",
            "Mailing_Address_Work" => "N",
            "indv_corp" => "I",
            "product_category" => "PERSONAL",
            "Sales_Executive_code" => "",
            "Start_Date_and_Time" => "",
            "Final_Submit_Date_and_Time" => "",
            "Aadhar" => "",
            "Voter_iD" => "",
            "Driving_License" => "",
            "Passport_no" => "",
            "Preapproved_Amount" => "",
            "Monthly_take_home_Salary" => $params['net_monthly_income'],
            "Proposed_EMI" => $encypProposedEmiStr,
            "Profession" => "",
            "CustomerID" => "",
            "AccountNo" => "",
            "cibil_chk_done" => "N",
            "existing_customer" => "N",
            "Los_AgreementID" => "",
            "Ref_No" => "",
            "flag" => "",
            "request_time" => Helpers::getDateByFormat(Helpers::getCurrentDateTime(), 'Y-m-d H:i:s', 'Y/m/d h:i:s A'),
            "deviceId" => Config::get("hdfcbank.$this->env.device_id"),
            "UniqueKey" => "HDFCMOBAPPU9Z60M5RMX",
            "Branch" => "",
            "Bank_Name" => "",
            "Bank_Branch" => "",
            "Account_Type" => "SAVINGS",
            "Account_No" => "",
            "Years_held" => "1",
            "Filler1" => "Website",
            "Filler2" => "",
            "Filler3" => "",
            "Filler4" => "",
            "Filler5" => "",
            "residence_type_dap" => "",
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
            "employment_type" => !empty($empType['lender_code']) ? $empType['lender_code'] : '',
            "Authorize" => "Yes",
            "PF" => $encypPfStr,
            "Promocode" => "",
            "IRR" => $encypIrrStr,
            "BTFlag" => "N",
            "campaign_name" => "",
            "kyc" => "",
            "WinId_EmpId" => "",
            "Type" => "",
            "Lead_Id" => "",
            "offer_details" => "",
            "SalAcc" => "Yes" 
        ]];
        return json_encode($data);
    }
    
    /**
     * Build HDFC Add Reference Request
     *
     * @param array $params, string $encryKey, string $hdfcAppId
     * @return string
     */
    public function buildAddReferenceRequest($params, $encryKey, $hdfcAppId)
    {
        $data = [
            "AddReference" => [   
                "Action" => "Yes",
                "cibil_Ack_no" => $hdfcAppId,
                "Religion" => "HN",
                "MaritalStatus" => "M",
                "Category" => "O",
                "ProfQualification" => "BE",
                "LoanPurpose" => "Person",
                "ref_1_Title" => "",
                "ref_1_FirstName" => "Ravi",
                "ref_1_LastName" => "",
                "ref_1_Relationship" => "",
                "ref_1_Address1" => "",
                "ref_1_Address2" => "",
                "ref_1_Address3" => "",
                "ref_1_State" => "",
                "ref_1_City" => "",
                "ref_1_Pincode" => "",
                "ref_1_Mobile" => "9787648464",
                "ref_1_Email" => "",
                "ref_2_Title" => "",
                "ref_2_FirstName" => "",
                "ref_2_LastName" => "",
                "ref_2_Relationship" => "",
                "ref_2_Address1" => "",
                "ref_2_Address2" => "",
                "ref_2_Address3" => "",
                "ref_2_State" => "",
                "ref_2_City" => "",
                "ref_2_Pincode" => "",
                "ref_2_Mobile" => "",
                "ref_2_Email" => "",
                "Filler1" => "Website",
                "Filler2" => "",
                "Filler3" => "",
                "Filler4" => "",
                "Filler5" => "",
                "deviceId" => Config::get("hdfcbank.$this->env.device_id"),
                "IsPreApprovedLoan" => "no",
                "Employer_Name" => "",
                "Employer_Name_other" => "",
                "STPFlag" => "" 
        ]];
        return json_encode($data);
    }
    
    /**
     * Build HDFC Document Upload API Request
     *
     * @param integer $matchId
     * @return string
     */
    public function buildDocumentUploadRequest($matchId)
    {
        $hdfcInfoData = Helpers::getHdfcInfoDataByMatchId($matchId);
        $byteArr = file_get_contents(public_path().'/images/checkbox_bg.png');
        $imageContent = base64_encode($byteArr);
        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $encypStringFiller2 = $this->httpClient->restClientPost(Config::get("hdfcbank.$this->env.hdfc_encryp_url2"), '{"device_unique_id": "'.Config::get("hdfcbank.$this->env.device_id").'", "lastReceivedToken": "'.$hdfcInfoData->encrypt_key_string.'"}', $headers);
        $encypRefNoStr = $this->httpClient->restClientPost(Config::get("hdfcbank.$this->env.hdfc_encryp_url1"), '{"request_text":"'.($hdfcInfoData->application_id).'"}', $headers);
        $data = [
            "doDocumentUpload" => [   
                "Ref_No" => $encypRefNoStr,
                "f" => $imageContent,
                "fileName" => "test.png",
                "ContentLength" => '124',
                "ContentType" => "image/png",
                "Parent_Doc_Id" => "6",
                "Child_Doc_Id" => "16",
                "deviceId" => Config::get("hdfcbank.$this->env.device_id"),
                "RequestTime" => Helpers::getDateByFormat(Helpers::getCurrentDateTime(), 'Y-m-d H:i:s', 'Y/m/d h:i:s A'),
                "UniqueKey" => "HDFCMOBAPPI9K8F14MO7",
                "Filler1" => "Website",
                "Filler2" => $encypStringFiller2,
                "Filler3" => "",
                "Filler4" => $this->generateAlphaNumericStr(),
                "Filler5" => "",
                "flgInternal" => "",
                "flgInsert" => ""  
        ]];
        
        return json_encode($data);
    }
    
    /**
     * Build HDFC Complete Document Upload Request
     *
     * @param integer $matchId
     * @return string
     */
    public function buildCompleteDocumentUploadRequest($matchId)
    {
        $hdfcInfoData = Helpers::getHdfcInfoDataByMatchId($matchId);
        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $encypStringFiller2 = $this->httpClient->restClientPost(Config::get("hdfcbank.$this->env.hdfc_encryp_url2"), '{"device_unique_id": "'.Config::get("hdfcbank.$this->env.device_id").'", "lastReceivedToken": "'.$hdfcInfoData->encrypt_key_string.'"}', $headers);
        $encypRefNoStr = $this->httpClient->restClientPost(Config::get("hdfcbank.$this->env.hdfc_encryp_url1"), '{"request_text":"'.($hdfcInfoData->encrypt_key_string.$hdfcInfoData->application_id.$hdfcInfoData->encrypt_key_string).'"}', $headers);
        $data = [
             "doCompleteDocumentUpload" => [       
                 "Ref_No" => $encypRefNoStr,
                 "deviceId" => Config::get("hdfcbank.$this->env.device_id"),
                 "RequestTime" => Helpers::getDateByFormat(Helpers::getCurrentDateTime(), 'Y-m-d H:i:s', 'Y/m/d h:i:s A'),
                 "UniqueKey" => "HDFCMOBAPP5RUCGF70ZQ",
                 "Filler1" => "Website",
                 "Filler2" => $encypStringFiller2,
                 "Filler3" => "",
                 "Filler4" => $this->generateAlphaNumericStr(),
                 "Filler5" => "",
                 "strFinalUpload" => "Y"
            ]];
            return json_encode($data); 
    }
    
    /**
     * Build HDFC Track Your Loan Request
     *
     * @param integer $matchId
     * @return string
     */
    public function buildTrackYourLoanRequest($matchId, $params)
    {
        $hdfcInfoData = Helpers::getHdfcInfoDataByMatchId($matchId);
        $data = [
             "getStatusEnquiry" => [       
                    "deviceId" => Config::get("hdfcbank.$this->env.device_id"),
                    "mobileNumber" => $params['mobile_no'],
                    "RequestTime" => Helpers::getDateByFormat(Helpers::getCurrentDateTime(), 'Y-m-d H:i:s', 'Y/m/d h:i:s A'),
                    "UniqueKey" => "HDFCMOBAPPM1LC61KYRR",
                    "applicationId" => '56947856',
                    "Filler1" => "Website",
                    "Filler2" => "",
                    "Filler3" => "",
                    "Filler4" => $this->generateAlphaNumericStr(),
                    "Filler5" => "",
                    "UserName" => Config::get("hdfcbank.$this->env.partner_username"),
                    "Password" => Config::get("hdfcbank.$this->env.partner_password")
            ]];
            return json_encode($data); 
    }
    /////////////////////////////////////////////////////////////////////

    /**
     * offer availability payload
     *
     * 
     * @return object
     */
    public function OfferAvailabilityPayload(){
        $payload =  '{    
            "FintechRequest": {
            "Transaction_refno": "123456",
            "Mobile_no": "9811985010",
            "Partner_name": "",
            "Source-name": "",
            "Identifier_Name": "PAN_NO",
            "Identifier_Value": "AMIPK4097R",
            "Product_name": "PL",
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
            } }'; 
        return $payload; 
    }

    /**
     * otp generation payload
     *
     * 
     * @return object
     */
    public function OtpGenerationPayload(){
       $payload = '{  "ccotpserviceRequest": 
       {
            "Trace_Number": "01070318000002445557",
            "Transaction_DateTimeStamp": "20181227T122339",
            "ATM_POS_IVR_ID": "API",
            "Credit_Card_Number": "0001012320000015806",
            "callerId": "hdfc_Api",
            "instanceId": "8888",
            "linkData": "0000000919811985010",
            "messageHash": "static:genpwdreq:06:3YTCIABuN2CSg18k10Vz/7Xxy28=",
            "refNo": "0107031800000248",
            "customerMobileNo": "919811985010",
            "otpPasswordValue": "",
            "sms_userid": "ccivrotp",
            "sms_password": "banker1245",
            "ctype": "1",
            "sender": "HDFCBank",
            "mobilenumber": "919811985010",
            "msgtxt": "Your confidential one time password for HDFC Bank authentication is #OTP#,
            valid for 2 hours. Kindly enter this OTP as prompted.",
            "departmentcode": "CCIVROTP",
            "submitdate": "2018-12-27 12:23:39",
            "author": "",
            "subAuthor": "",
            "broadcastname": "CCIVROTP",
            "internationalflag": "0",  
            "msgid": "0107031800000745",
            "drlflag": "",
            "dndalert": "",
            "msgtype": "S",
            "priority": "",
            "authType": "2",
            "appname":"",
            "appCodeHash":"",
            "SOAStandardElements": {
                "service_user": "",
                "service_password": "",
                "consumer_name": ""   
            },
                "soafillers": {
                    "filler1": "",
                    "filler2": "",
                    "filler3": "",
                    "filler4": "",
                    "filler5": ""
                }  
            } 
        }';
    }

    /**
     * prepare otp generation request
     *
     * 
     * @return array $params
     */
    public function OtpGenerationRequest(){
        $key = $this->generateRandomString(32);
        $payload = $this->OtpGenerationPayload();
        dd("payload",$payload);
        $params = [];
        $params['RequestSignatureEncryptedValue'] = $this->getRequestSignatureEncryptedValue($payload,$key);
        $params['SymmetricKeyEncryptedValue'] = $this->getSymmetricKeyEncryptedValue($key);
        $params['Scope'] = "Andromeda";
        $params['TransactionId'] = "";
        return json_encode($params); 
    }

    /**
     * prepare offer availability request
     *
     * 
     * @return array $params
     */
    public function OfferAvailabilityRequest(){
        $key = $this->generateRandomString(32);
        $payload = $this->OfferAvailabilityPayload();
        $params = [];
        $params['RequestSignatureEncryptedValue'] = $this->getRequestSignatureEncryptedValue($payload,$key);
        $params['SymmetricKeyEncryptedValue'] = $this->getSymmetricKeyEncryptedValue($key);
        $params['Scope'] = "Andromeda";
        $params['TransactionId'] = "";
        return json_encode($params); 
    }

    /**
     * encrypt signature
     *
     * @param object $payload, string $randomkey
     * @return string
     */
    public function getRequestSignatureEncryptedValue($payload,$randomkey){
        $payload = json_decode($payload, true); 
        $private_key = $this->getCertificatePrivateKey();
       
        //digitally sign the request using RS256
        $jwt = JWT::encode($payload, $private_key, "RS256");
       
        //$jwt_encoded = base64_encode($jwt); // may not required
        $encrypted_digital_signature = $this->encryptSignature($jwt,$randomkey);
        return $encrypted_digital_signature; //rr
    }

    /**
     * encrypt signature
     *
     * @param string $jwt, string $randomkey
     * @return string
     */
    public function encryptSignature($jwt,$randomkey){
        //encrypt the signature using random string,pkcs5 
            $cipher="AES-256-CBC";
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $jwt = $iv.$jwt;
            $req_digital_signature = openssl_encrypt($jwt,'AES-256-CBC',$randomkey,false,$iv);
            
            return $req_digital_signature;
    }

    /**
     * encrypt key
     *
     * @param string $randomkey
     * @return string
     */
    public function getSymmetricKeyEncryptedValue($randomkey){ 
        //$key_encoded = base64_encode($key);  // may not required 
        $hdfc_public_key = $this->getCertificatePublicKey();
        $encrypted_key = $this->encryptKey($randomkey,$hdfc_public_key);
        $encrypted_key = base64_encode($encrypted_key); //imp
        return $encrypted_key;
    }

    /**
     * encrypt key
     *
     * @param string $key,string $hdfc_public_key
     * @return string
     */
    public function encryptKey($key,$hdfc_public_key){
        //asymmetric key encryption pkcs1 padding
        $bool = openssl_public_encrypt($key,$crypted,$hdfc_public_key,OPENSSL_PKCS1_PADDING);
        return $crypted;
    }

    //Commons Functions Related to Encryption/Decryption
    /**
     * generate certificate private key
     *
     * 
     * @return string
     */
    public function getCertificatePrivateKey(){
        $private_key = file_get_contents('/etc/httpd/ssl/andromeda-dev/certificate.key');
        return $private_key;
    }

    /**
     * generate certificate public key
     *
     * 
     * @return string
     */
    public function getCertificatePublicKey(){
        $hdfc_certificate = openssl_pkey_get_public(file_get_contents(__DIR__."/publickey.cer")); 
        $hdfc_certificate_details = openssl_pkey_get_details($hdfc_certificate);
        return $hdfc_certificate_details['key'];
    }

    /**
     * generate random string
     *
     * @param integer $length
     * @return string
     */
    public function generateRandomString($length = 32){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    //RESPONSE RELATED FUNCTIONS

    /**
     * offer availability request
     *
     * @param array $responseData
     * @return array
     */
    public function OfferAvailabilityResponse($responseData){

        $data = [];
        $data['GWSymmetricKeyDecryptedValue'] = $this->DecryptKey($responseData['GWSymmetricKeyEncryptedValue']);
        $data['ResponseSignatureDecryptedValue'] = $this->DecryptSignature($responseData['ResponseSignatureEncryptedValue'],$data['GWSymmetricKeyDecryptedValue']);
        $data['Scope'] = $responseData['Scope'];
        $data['TransactionId'] = $responseData['TransactionId'];

        //verify signature and generate payload
        $public_key = $this->getCertificatePublicKey();
        $payload = JWT::decode($data['ResponseSignatureDecryptedValue'], $public_key, array('RS256'));
        return $payload; 
    }

    /**
     * Decrypt signature
     *
     * @param string $SignatureEncryptedValue, string $decrypted_key
     * @return string
     */
    public function DecryptSignature($SignatureEncryptedValue,$decrypted_key)
    {   
        $cipher="AES-256-CBC";
        $ivlen = openssl_cipher_iv_length($cipher); //16
        $iv = openssl_random_pseudo_bytes($ivlen);
        $decrypted_digital_signature = openssl_decrypt($SignatureEncryptedValue,'AES-256-CBC',$decrypted_key,false,$iv);

        ///$decrypted_digital_signature = filter_var($decrypted_digital_signature, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
        $pos = strpos($decrypted_digital_signature,"eyJ");
        $decrypted_digital_signature =substr($decrypted_digital_signature,$pos);
        //$decrypted_digital_signature = base64_decode($decrypted_digital_signature); //must //mproblem
        return $decrypted_digital_signature;
    }

    /**
     * Decrypt encrypted key
     *
     * @param sting $encrypted_key
     * @return string
     */
    public function DecryptKey($encrypted_key){        
        $hdfc_private_key = $this->getCertificatePrivateKey();
        $encrypted_key = base64_decode($encrypted_key); //must
        $bool =openssl_private_decrypt($encrypted_key,$decrypted,$hdfc_private_key,OPENSSL_PKCS1_PADDING);
        return $decrypted;
        
    }
}


