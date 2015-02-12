<?php 
/**
 * PHP library for trsansaction services API
 *
 * This is a php wrapper class that implements the necessary functions reuired
 * to perform the basic functionalities of the transaction services API 
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to open-sourced software licensed 
 *          under the [MIT license](http://opensource.org/licenses/MIT)
 */
 
require_once('padCrypt.php');
require_once('AES_Encryption.php');
require_once('xmlparser.php');

class Transx_lib{
    
    public $url = 'https://api.trxservices.net/';
    
    private $algorithm_key;
    private $algorithm_iv;
    
    private $xml_detail      = null;
    private $xml_auth        = null;
    private $xml_account     = null;
    private $xml_industry    = null;
    private $xml_storagesafe = null;
    private $xml_reference   = null;
    private $xml = '';
    private $encrypted = '';
    private $unencrypted = '';
    private $response_array = array();
    
    private function hexa2bina($str) {
        $bin = "";
        $i = 0;
        do {
            $bin .= chr(hexdec($str{$i}.$str{($i + 1)}));
            $i += 2;
        } while ($i < strlen($str));
        return $bin;
    }
    
    public function setKeyIv($key="",$iv="")
    {
        $this->algorithm_key = $this->hexa2bina($key);
        $this->algorithm_iv  = $this->hexa2bina($iv);
        
    }
    
    public function setDetail($mode="Credit",$action="Sale",$amount=0.00,$currency_code=840,$options=array())
    {
        $this->xml_detail  =  '<Detail>';
        $this->xml_detail .=  '<TranType>'.$mode.'</TranType>';
        $this->xml_detail .=  '<TranAction>'.$action.'</TranAction>';
        if(!is_null($amount)):
        $this->xml_detail .=  '<Amount>'.number_format($amount,2).'</Amount>';
        endif;
        if(!is_null($currency_code)):
        $this->xml_detail .=  '<CurrencyCode>'.$currency_code.'</CurrencyCode>';
        endif;
        if(count($options) > 0):
            foreach($options as $key=>$val):
            $this->xml_detail .=  '<'.$key.'>'.$val.'</'.$key.'>';
            endforeach;
        endif;
        $this->xml_detail .=  '</Detail>';
        
    }
    
    private function getDetail()
    {
        return $this->xml_detail;
    }
    
    public function setReference($options=array())
    {
        if(count($options) > 0):
        $this->xml_reference  =  '<Reference>';
            foreach($options as $key=>$val):
            $this->xml_reference .=  '<'.$key.'>'.$val.'</'.$key.'>';
            endforeach;
        $this->xml_reference .=  '</Reference>';
        endif;
    }
    
    private function getReference()
    {
        return $this->xml_reference;
    }
    
    public function setAuthentication($client_id = null, $source_id = null)
    {
        $this->xml_auth = '<Authentication><Client>'.$client_id.'</Client><Source>'.$source_id.'</Source></Authentication>';
        
    }
    
    private function getAuthentication()
    {
        return $this->xml_auth;
    }
    
    public function setAccount($parameters=array())
    {
        $this->xml_account  = '<Account>';   
        foreach($parameters as $key=>$val):
            $this->xml_account .= '<'.$key.'>'.$val.'</'.$key.'>';
        endforeach;
        $this->xml_account .= '</Account>';
        
    }
    
    private function getAccount()
    {
        return $this->xml_account;
    }
    
    public function setIndustryData($industry="CardNotPresent",$eci=7)
    {
        $this->xml_industry = '<IndustryData><Industry>'.$industry.'</Industry><Eci>'.$eci.'</Eci></IndustryData>';
        
        
    }
    
    public function setStorageSafe($guid=null)
    {
        if($guid == null):
        $this->xml_storagesafe = '<StorageSafe><Generate>1</Generate></StorageSafe>';
        else:
        $this->xml_storagesafe = '<StorageSafe><Guid>'.$guid.'</Guid></StorageSafe>';
        endif;
    }
    
    private function getStorageSafe()
    {
        return $this->xml_storagesafe;
    }
    
    private function getIndustryData()
    {
        return $this->xml_industry;
    }
    
    public function encryptRequest()
    {
        $aes = new AES_Encryption($this->algorithm_key, $this->algorithm_iv);
        
        $xml = '';
        
        if($this->xml_detail != null){
            $xml .= $this->getDetail();
        }
        
        if($this->xml_reference != null){
            $xml .= $this->getReference();
        }
        
        if($this->xml_storagesafe != null){
            $xml .= $this->getStorageSafe();
        }
        
        if($this->xml_industry != null){
            $xml .= $this->getIndustryData();
        }
        
        if($this->xml_account != null){
            $xml .= $this->getAccount();
        }
        
        $this->unencrypted = $xml;
        
        $this->encrypted = base64_encode($aes->encrypt($xml));
        
        
    }
    
    public function getUnencryptedXml()
    {
        return $this->unencrypted;
    }
    
    public function getFullResponse()
    {
        return $this->response_array;
    }
    
    public function decryptRequest($output="")
    {
    	$parser = new XmlParser($output);	
    	$xml_output = $parser->parse($output);
    	
    	foreach($xml_output[0]['children'] as $response){
    		if($response['tag'] == 'RESPONSE'){
    			$response_data = $response['data'];
    		}
    	}
    	
        $aes = new AES_Encryption($this->algorithm_key, $this->algorithm_iv);
	    $responseXML = "<responseXML>".$aes->decrypt(base64_decode($response_data))."</responseXML>";
	    
    	$parser123 = new XMLParser($output);
    	$response_result = $parser123->parse($responseXML);
    	
    	$this->response_array = $response_result;
    	
    	$xmlResult = array();
    	foreach($response_result[0]['children'] as $result){
    		if($result['tag'] == 'REFERENCE'){
    			foreach($result['children'] as $reference_result){
    				$xmlResult['REFERENCE'][$reference_result['tag']] = $reference_result['data'];		
    			}
    		}
    		if($result['tag'] == 'RESULT'){
    			foreach($result['children'] as $reference_result){
    				$xmlResult['RESULT'][$reference_result['tag']] = $reference_result['data'];	
    			}
    		}
    		if($result['tag'] == 'STORAGESAFE'){
    			foreach($result['children'] as $reference_result){
    				$xmlResult['STORAGESAFE'][$reference_result['tag']] = $reference_result['data'];
    			}
    		}
    	}
    	
    	return $xmlResult;
    }
    
    public function getDirectXml()
    {
        $this->xml = '<Message>';
        
        $this->xml .= '<Request>'.$this->encrypted.'</Request>';
        
        if($this->xml_auth != null){
            $this->xml .= $this->getAuthentication();
        }
        
        $this->xml .= '</Message>';
        
        return $this->xml;
        
    }
    
    public function requestcurl($xml=null)
    {
        
        if($xml == null){ $xml = $this->xml; }
    	$ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL,$this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml')); 
        $result=curl_exec($ch);
        
    	return $result;
    }    
}