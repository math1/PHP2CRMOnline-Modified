

<?php
include_once "LiveIdManager.php";
include_once "EntityUtils.php";

//names are unchanged from PHP2CRMOnline lab.
$liveIDUseranme = "username@yourCRMOnlineInstance.onmicrosoft.com";
$liveIDPassword = "yourpassword";



$organizationServiceURL = "https://yourCRMOnlineInstance.api.crm4.dynamics.com/XRMServices/2011/Organization.svc"; 

$liveIDManager = new LiveIDManager();

$securityData = $liveIDManager->authenticateWithLiveID( $organizationServiceURL, $liveIDUseranme, $liveIDPassword );
 
//Print out the token received from WLID

if($securityData!=null && isset($securityData)){
    echo ("\nKey Identifier:" . $securityData->getKeyIdentifier());
    echo ("\nSecurity Token 1:" . $securityData->getSecurityToken0());
    echo ("\nSecurity Token 2:" . $securityData->getSecurityToken1());
}else{
    echo "Unable to authenticate LiveId.";
    return;
}


    function createAccount($CRMURL,$securityData) {

        $domainname = substr($CRMURL,8,-1);
        $pos = strpos($domainname, "/");
        $domainname = substr($domainname,0,$pos);
       
        $accountsRequest = EntityUtils::getCreateCRMSoapHeader($CRMURL, $securityData).
                '<s:Body>
 <Create xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
    <entity xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
        <b:Attributes xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
            <b:KeyValuePairOfstringanyType>
                <c:key>name</c:key>
                <c:value i:type="d:string" xmlns:d="http://www.w3.org/2001/XMLSchema">Väinämöinen</c:value>
            </b:KeyValuePairOfstringanyType>
            <b:KeyValuePairOfstringanyType>
                <c:key>accountcategorycode</c:key>
                <c:value i:type="b:OptionSetValue">
                <b:Value>1</b:Value>
                </c:value>
            </b:KeyValuePairOfstringanyType>
            <b:KeyValuePairOfstringanyType>
                <c:key>customertypecode</c:key>
                <c:value i:type="b:OptionSetValue">
                <b:Value>4</b:Value>
                </c:value>
            </b:KeyValuePairOfstringanyType>
        </b:Attributes>

        <b:EntityState i:nil="true"/>
        <b:FormattedValues xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
        <b:Id>00000000-0000-0000-0000-000000000000</b:Id>
            <b:LogicalName>account</b:LogicalName>
            <b:RelatedEntities xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
        </entity>
    </Create> 
    </s:Body>
 </s:Envelope>
			';
                
        
        echo ("\naccountsRequest:" . $accountsRequest);
	$response =  LiveIDManager::GetSOAPResponse("/Organization.svc", $domainname, $CRMURL, $accountsRequest);
        echo ("\nresponse:" . $response);
        $createResult ="";
        if($response!=null && $response!=""){
            preg_match('/<CreateResult>(.*)<\/CreateResult>/', $response, $matches);
            $createResult =  $matches[1];
        }
        
        return $createResult;

    }
    function readAccount($accountId,$CRMURL,$securityData){
        
        $domainname = substr($CRMURL,8,-1);
        
        $pos = strpos($domainname, "/");

        $domainname = substr($domainname,0,$pos);
        
        $accountsRequest = EntityUtils::getCRMSoapHeader($CRMURL, $securityData) .
        '
              <s:Body>
                    <Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                            <request i:type="b:RetrieveMultipleRequest" xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                                    <b:Parameters xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
                                            <b:KeyValuePairOfstringanyType>
                                                    <c:key>Query</c:key>
                                                    <c:value i:type="b:FetchExpression">
                                                            <b:Query>&lt;fetch mapping="logical" count="50" version="1.0"&gt;&#xD;
                                                                    &lt;entity name="account"&gt;&#xD;
                                                                    &lt;attribute name="name" /&gt;&#xD;
                                                                    &lt;attribute name="address1_city" /&gt;&#xD;
                                                                    &lt;attribute name="telephone1" /&gt;&#xD;
                                                                    &lt;filter type="and"&gt;
                                                                        &lt;condition attribute="accountid" operator="eq" value="'.$accountId.'" /&gt;
                                                                    &lt;/filter&gt;
                                                                    &lt;/entity&gt;&#xD;
                                                                    &lt;/fetch&gt;
                                                            </b:Query>
                                                    </c:value>
                                            </b:KeyValuePairOfstringanyType>
                                    </b:Parameters>
                                    <b:RequestId i:nil="true"/><b:RequestName>RetrieveMultiple</b:RequestName>
                            </request>
                    </Execute>
                    </s:Body>
            </s:Envelope>
			';
	$response =  LiveIDManager::GetSOAPResponse("/Organization.svc", $domainname, $CRMURL, $accountsRequest);

        $accountsArray = array();
        if($response!=null && $response!=""){
        
            $responsedom = new DomDocument();
            $responsedom->loadXML($response);
            $entities = $responsedom->getElementsbyTagName("Entity");
            foreach($entities as $entity){
                    $account = array();
                    $kvptypes = $entity->getElementsbyTagName("KeyValuePairOfstringanyType");
                    foreach($kvptypes as $kvp){
                            $key =  $kvp->getElementsbyTagName("key")->item(0)->textContent;
                            $value =  $kvp->getElementsbyTagName("value")->item(0)->textContent;					
                            if($key == 'accountid'){ $account['accountId'] = $value; }
                            if($key == 'name'){ $account['name'] = $value; }
                            if($key == 'telephone1'){ $account['telephone'] = $value; }					
                            if($key == 'address1_city'){ $account['address'] = $value; }										
                    }
                    $accountsArray[] = $account;
            }
        }
        return $accountsArray;
    }
    
        function deleteAccount($accountId,$CRMURL,$securityData) {
        
        $domainname = substr($CRMURL,8,-1);
        $pos        = strpos($domainname, "/");
        $domainname = substr($domainname,0,$pos);
        $accountsRequest = EntityUtils::getDeleteCRMSoapHeader($CRMURL, $securityData).
            '<s:Body>
                <Delete xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                    <entityName>account</entityName>
                    <id>'.$accountId.'</id>
                </Delete>
            </s:Body>
        </s:Envelope>';
       	$response =  LiveIDManager::GetSOAPResponse("/Organization.svc", $domainname, $CRMURL, $accountsRequest);
    }

$accountId = 'F7535601-D61E-E311-B043-984BE17C47E4';

$accountId = createAccount($organizationServiceURL, $securityData);
$response = readAccount($accountId,$organizationServiceURL,$securityData);
//echo ("\nresponse:" . $response);
print_r(readAccount($accountId, $organizationServiceURL, $securityData));
deleteAccount($accountId,$organizationServiceURL,$securityData);



/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
