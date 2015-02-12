# PHP Library for Transaction Services Payment Gateay API
> This is a php wrapper class that implements the necessary functions reuired to perform the basic functionalities of the transaction services API. To know more visit their website at http://www.trxservices.com/

# Examples:
## Credit Sale
    include('transx_lib.php');   
    $client_id = YOUR CLIENT ID; //You need to give your client id
    $direct = new Transx_lib;
    $direct->setKeyIv("YOUR KEY","YOUR IV"); //You need to give your Key and IV
    $amount = 12;
    $direct->setDetail("Credit","Sale",number_format($amount,2),840);
    $data['FirstName'] = 'John';
    $data['LastName'] = 'Doe';
    $data['Pan'] = '4111111111111111';
    $data['Expiration'] = '1217';
    $data['Postal'] = '12345';
    $data['Address'] = '123 Lake Tarrace';
    $direct->setAccount($data);
    $direct->setAuthentication($client_id,1);
    $direct->setIndustryData("CardNotPresent",7);
    $direct->encryptRequest();
    $xml = $direct->getDirectXml();
    $response = $direct->requestcurl($xml);
    $output = $direct->decryptRequest($response);
    echo '<pre>';
    print_r($output);
    
