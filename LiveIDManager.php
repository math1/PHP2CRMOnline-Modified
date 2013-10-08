<?php

include_once "DeviceIdManager.php";
include_once "SecurityData.php";


class LiveIDManager {

    public function authenticateWithLiveID($CRMUrl, $liveIDUsername, $liveIDPassword) {

     

     //send username and pass to get tokens. please rename everything as you like :) 

                
                    $deviceCredentialsSoapTemplate = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"
	                xmlns:a="http://www.w3.org/2005/08/addressing"
	                xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
	                <s:Header>
		                <a:Action s:mustUnderstand="1">http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue
		                </a:Action>
		                <a:MessageID>urn:uuid:%s
		                </a:MessageID>
		                <a:ReplyTo>
			                <a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
		                </a:ReplyTo>
		                <VsDebuggerCausalityData
			                xmlns="http://schemas.microsoft.com/vstudio/diagnostics/servicemodelsink">uIDPo4TBVw9fIMZFmc7ZFxBXIcYAAAAAbd1LF/fnfUOzaja8sGev0GKsBdINtR5Jt13WPsZ9dPgACQAA
		                </VsDebuggerCausalityData>
		                <a:To s:mustUnderstand="1">https://login.microsoftonline.com/RST2.srf
		                </a:To>
		                <o:Security s:mustUnderstand="1"
			                xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			                <u:Timestamp u:Id="_0">
				                <u:Created>%sZ</u:Created>
				                <u:Expires>%sZ</u:Expires>
			                </u:Timestamp>
			                <o:UsernameToken u:Id="uuid-%s">
				                <o:Username>%s</o:Username>
				        <o:Password
Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">%s</o:Password>
			                </o:UsernameToken>
		                </o:Security>
	                </s:Header>
	                <s:Body>
		                <t:RequestSecurityToken xmlns:t="http://schemas.xmlsoap.org/ws/2005/02/trust">
			                <wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
				                <a:EndpointReference>
					                <a:Address>urn:crmemea:dynamics.com</a:Address>
				                </a:EndpointReference>
			                </wsp:AppliesTo>
			                <t:RequestType>http://schemas.xmlsoap.org/ws/2005/02/trust/Issue
			                </t:RequestType>
		                </t:RequestSecurityToken>
	                </s:Body>
                </s:Envelope>';

                
                
              

        $soapTemplate = sprintf(
                $deviceCredentialsSoapTemplate, LiveIDManager::gen_uuid(), LiveIDManager::getCurrentTime(), LiveIDManager::getNextDayTime(), LiveIDManager::gen_uuid(), $liveIDUsername, $liveIDPassword);

    
        $binaryDATokenXML = LiveIDManager::GetSOAPResponse("/RST2.srf" , "login.microsoftonline.com" , "https://login.microsoftonline.com/RST2.srf", $soapTemplate);
        echo ("\nbinaryDAToken:" . $binaryDATokenXML);

        $responsedom = new DomDocument();
  
        $responsedom->loadXML($binaryDATokenXML);
		
        $cipherValues = $responsedom->getElementsbyTagName("CipherValue");


        
        if( isset ($cipherValues) && $cipherValues->length>0
            ){
            $securityToken0 =  $cipherValues->item(0)->textContent;
            $securityToken1 =  $cipherValues->item(1)->textContent;
            $keyIdentifier = $responsedom->getElementsbyTagName("KeyIdentifier")->item(0)->textContent;	
        }else{
            return null;
        }
                echo ("\nsecurityToken0:" . $securityToken0);
        echo ("\nsecurityToken1:" . $securityToken1);
        echo ("\nkeyIdentifier:" . $keyIdentifier);
        
         //$securityTemplate = sprintf(
         //$securityTokenSoapTemplate2,  LiveIDManager::getCurrentTime(), LiveIDManager::getNextDayTime(),$keyIdentifier, $cipherValues->item(0)->textContent, $cipherValues->item(1)->textContent);
//$securityTokenXML = LiveIDManager::GetSOAPResponse("/v2/wstrust/13/issuedtoken-asymmetric" , "dynamicscrmemea.accesscontrol.windows.net" , "https://dynamicscrmemea.accesscontrol.windows.net/v2/wstrust/13/issuedtoken-asymmetric", $securityTemplate);        
         //echo ("\nsecurityToken0:" . $securityToken0);
         //echo ("\nsecurityToken1:" . $securityToken1);
         //echo ("\nkeyIdentifier:" . $keyIdentifier);
         //echo ("\nsecurityTokenXML:" . $securityTokenXML);
        
         $newSecurityData = new SecurityData($keyIdentifier, $securityToken0, $securityToken1);
        return $newSecurityData;
    }

    public static function getCurrentTime() {
        return substr(date('c'), 0, -6) . ".00";
    }

    public static function getNextDayTime() {
        return substr(date('c', strtotime('+1 day')), 0, -6) . ".00";
    }

    public static function gen_uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                // 32 bits for "time_low"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0x0fff) | 0x4000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                mt_rand(0, 0x3fff) | 0x8000,
                // 48 bits for "node"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function GetSOAPResponse($postUrl, $hostname, $soapUrl, $content) {

        // setup headers
        $headers = array(
            "POST " . $postUrl . " HTTP/1.1",
            "Host: " . $hostname,
            'Connection: Keep-Alive',
            "Content-type: application/soap+xml; charset=UTF-8",
            "Content-length: " . strlen($content),
        );

        $cURLHandle = curl_init();
        

    
        curl_setopt($cURLHandle, CURLOPT_URL, $soapUrl);
        curl_setopt($cURLHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($cURLHandle, CURLOPT_TIMEOUT, 60);
        curl_setopt($cURLHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURLHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($cURLHandle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($cURLHandle, CURLOPT_POST, 1);
        curl_setopt($cURLHandle, CURLOPT_POSTFIELDS, $content);
        curl_setopt($cURLHandle, CURLOPT_SSLVERSION , 3);
        $response = curl_exec($cURLHandle);
        curl_close($cURLHandle);

        return $response;
    }

}

?>
