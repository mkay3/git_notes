<?php

namespace hdfc2\hdfc;

use Illuminate\Http\Request;
use App\Http\Requests;
    use Crypt;
    use \Firebase\JWT\JWT;
    use App\Http\Controllers\Controller;
    use Illuminate\Filesystem\Filesystem;

class TestController extends Controller
{
    
   

    /**
        * Filesystem object
        *
        * @var Illuminate\Filesystem\Filesystem
        */
        protected $file;
    
    public function __construct(){
            $this->file = new Filesystem();
    }

    public function encryption(){
    
        $private_key = $this->file->get(config('jwt.jwt_private_key_file')); 
        $public_key = $this->file->get(config('jwt.jwt_public_key_file'));
        
       /* $payload = {  
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
        }
        */
        
        $payload = [
            'name' => 'Abc',
            'mobile' => '9876543211',
            'email' => 'abc@test.com',
            'pan_no' => 'ABCDE7789F',
        ];

        //digitally sign the request using RS256
        $jwt = JWT::encode($payload, $private_key, "RS256");

        //Base64 encode the result
        $jwt_encoded = base64_encode($jwt);
        
        //Generate 32 bytes random string
        $key = $this->generate_random_key();
        
        //Encrypt the signature using random string //AES,PKCS5
            $cipher = "apache_setenv(variable, value)-256-gcm";
            $tag = "gcm";
            if (in_array($cipher, openssl_get_cipher_methods()))
            {
                $ivlen = openssl_cipher_iv_length($cipher);
                $iv = openssl_random_pseudo_bytes($ivlen);
                // FIRST PARAMETER : RequestSignatureEncryptedValue
                $req_digital_signature = openssl_encrypt($jwt_encoded, $cipher, $key, $options=0, $iv, $tag);
            }
        //dd($req_digital_signature);
        //Base64 encode the key
        $key_encoded = base64_encode($key);

       
        $key = "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";
        //$publickey = base64_decode($publickey);
        //dd($a);
        //Asymmetric encryption of the base64 encoded key using HDFC public key //RSA,PKCS1
        $rsa = new \phpseclib\Crypt\RSA();
        //extract($rsa->createKey());
        // $rsa->loadKey($publickey);
        $rsa->loadKey($key);
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1); 
        $result = $rsa->encrypt($key_encoded, 2);
        dd($result);
        //$rsa->_parseKey($publickey,true);
      
        // SECOND PARAMETER : SymmetricKeyEncryptedValue
        $key_encrypted = $rsa->encrypt($key_encoded);
        dd($key_encrypted);
        //Passing Symmetric key Parameter
        $sym_key_enc = base64_encode($key_encrypted);
        dd($sym_key_enc);
        
        $this->decryption2($req_digital_signature,$sym_key_enc);
        //////////////////////////////////////////////////////////////////////////////////////////
        /*   MAIN CODE
        $sym_key_enc_decoded = base64_decode($sym_key_enc);
        
        //$rsa = new \phpseclib\Crypt\RSA();
        //extract($rsa->createKey());
        $rsa->loadKey($publickey);
        $key_decrypted = $rsa->decrypt($sym_key_enc_decoded);
        //dd($key_decrypted);
        $key_decoded = base64_decode($key_decrypted); // decrypted final value
        //dd($key_decoded);

       // $cipher = "aes-256-gcm";
       // $tag = "gcm";
        if (in_array($cipher, openssl_get_cipher_methods()))
            {
                $original_plaintext = openssl_decrypt($req_digital_signature, $cipher, $key, $options=0, $iv, $tag);
            }
        //dd($original_plaintext);
        $jwt_decrypted = $original_plaintext;
        $jwt_decoded = base64_decode($jwt_decrypted);
        $request = JWT::decode($jwt_decoded, $public_key, array('RS256')); // decrypted final value
        dd((array)$request); */
        /////////////////////////////////////////////////////////////////////////////

    }

     public function decryption2($req_digital_signature="",$sym_key_enc="",$rsa){
         $sym_key_enc_decoded = base64_decode($sym_key_enc);
        
        //$rsa = new \phpseclib\Crypt\RSA();
        //extract($rsa->createKey());
        $rsa->loadKey($publickey);
        $key_decrypted = $rsa->decrypt($sym_key_enc_decoded);
        //dd($key_decrypted);
        $key_decoded = base64_decode($key_decrypted); // decrypted final value
        //dd($key_decoded);

       // $cipher = "aes-256-gcm";
       // $tag = "gcm";
        if (in_array($cipher, openssl_get_cipher_methods()))
            {
                $original_plaintext = openssl_decrypt($req_digital_signature, $cipher, $key, $options=0, $iv, $tag);
            }
        //dd($original_plaintext);
        $jwt_decrypted = $original_plaintext;
        $jwt_decoded = base64_decode($jwt_decrypted);
        $request = JWT::decode($jwt_decoded, $public_key, array('RS256')); // decrypted final value
        dd((array)$request);

     }

    public function decryption($req_digital_signature="",$sym_key_enc=""){
        //DUMMY


        $private_key = $this->file->get(config('jwt.jwt_private_key_file')); 
        $public_key = $this->file->get(config('jwt.jwt_public_key_file'));
        //decrypted key
        $sym_key_enc_decoded = base64_decode($sym_key_enc);
        
        $rsa = new \phpseclib\Crypt\RSA();
        extract($rsa->createKey());
        $rsa->loadKey($publickey);
        $key_decrypted = $rsa->decrypt($sym_key_enc_decoded);
        dd($key_decrypted);
        $key_decoded = base64_decode($key_decrypted); // decrypted final value
        //dd($key_decoded);

        $cipher = "aes-256-gcm";
        $tag = "gcm";
        if (in_array($cipher, openssl_get_cipher_methods()))
            {
                $ivlen = openssl_cipher_iv_length($cipher);
                $iv = openssl_random_pseudo_bytes($ivlen);
                $original_plaintext = openssl_decrypt($req_digital_signature, $cipher, $symmetric_key, $options=0, $iv, $tag);
                echo $original_plaintext."\n";
            }
    }

    public function generate_random_key(){
        $bytes = random_bytes(16);
        $random_key = bin2hex($bytes);
        return $random_key;
    }


}
