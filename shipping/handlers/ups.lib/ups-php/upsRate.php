<?php
class upsRate {
    var $AccessLicenseNumber;  
    var $UserId;  
    var $Password;
    var $shipperNumber;
    var $credentials;

    function setCredentials($access,$user,$pass,$shipper) {
	$this->AccessLicenseNumber = $access;
	$this->UserID = $user;
	$this->Password = $pass;	
	$this->shipperNumber = $shipper;
	$this->credentials = 1;
    }

    // Define the function getRate() - no parameters
    function getRate($source_country, $dest_country, $PostalCode,$dest_zip,$service,$length,$width,$height,$weight) {
	if ($this->credentials != 1) {
		print 'Please set your credentials with the setCredentials function';
		die();
	}
	$data ="<?xml version=\"1.0\"?>  
		<AccessRequest xml:lang=\"en-US\">  
		    <AccessLicenseNumber>$this->AccessLicenseNumber</AccessLicenseNumber>  
		    <UserId>$this->UserID</UserId>  
		    <Password>$this->Password</Password>  
		</AccessRequest>  
		<?xml version=\"1.0\"?>  
		<RatingServiceSelectionRequest xml:lang=\"en-US\">  
		    <Request>  
			<TransactionReference>  
			    <CustomerContext>Bare Bones Rate Request</CustomerContext>  
			    <XpciVersion>1.0001</XpciVersion>  
			</TransactionReference>  
			<RequestAction>Rate</RequestAction>  
			<RequestOption>Rate</RequestOption>  
		    </Request>  
		<PickupType>  
		    <Code>01</Code>  
		</PickupType>  
		<Shipment>  
		    <Shipper>  
			<Address>  
			    <PostalCode>$PostalCode</PostalCode>  
			    <CountryCode>{$source_country}</CountryCode>  
			</Address>  
		    <ShipperNumber>{$this->shipperNumber}</ShipperNumber>  
		    </Shipper>  
		    <ShipTo>  
			<Address>  
			    <PostalCode>$dest_zip</PostalCode>  
			    <CountryCode>{$dest_country}</CountryCode>  
			<ResidentialAddressIndicator/>  
			</Address>  
		    </ShipTo>  
		    <ShipFrom>  
			<Address>  
			    <PostalCode>$PostalCode</PostalCode>  
			    <CountryCode>{$source_country}</CountryCode>  
			</Address>  
		    </ShipFrom>  
		    <Service>  
			<Code>$service</Code>  
		    </Service>";
	if ( !is_array($length) ) $length = array($length);
	if ( !is_array($width) ) $width = array($width);
	if ( !is_array($height) ) $height = array($height);
	if ( !is_array($weight) ) $weight = array($weight);
	for ($i=0;$i<count($length); $i++){
		$data .= "
		    <Package>  
			<PackagingType>  
			    <Code>02</Code>  
			</PackagingType>  
			<Dimensions>  
			    <UnitOfMeasurement>  
				<Code>IN</Code>  
			    </UnitOfMeasurement>  
			    <Length>{$length[$i]}</Length>  
			    <Width>{$width[$i]}</Width>  
			    <Height>{$height[$i]}</Height>  
			</Dimensions>  
			<PackageWeight>  
			    <UnitOfMeasurement>  
				<Code>LBS</Code>  
			    </UnitOfMeasurement>  
			    <Weight>{$weight[$i]}</Weight>  
			</PackageWeight>  
		    </Package>";
	}
	$data .= "	    
		</Shipment>  
		</RatingServiceSelectionRequest>";  
		$ch = curl_init("https://www.ups.com/ups.app/xml/Rate");  
		curl_setopt($ch, CURLOPT_HEADER, 1);  
		curl_setopt($ch,CURLOPT_POST,1);  
		curl_setopt($ch,CURLOPT_TIMEOUT, 60);  
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);  
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
		curl_setopt($ch,CURLOPT_POSTFIELDS,$data);  
		$result=curl_exec ($ch);  
	    //echo '<!-- '. $result. ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS  
		$data = strstr($result, '<?');  
		$xml_parser = xml_parser_create();  
		xml_parse_into_struct($xml_parser, $data, $vals, $index);  
		xml_parser_free($xml_parser);  
		$params = array();  
		$level = array();  
		foreach ($vals as $xml_elem) {  
		 if ($xml_elem['type'] == 'open') {  
		if (array_key_exists('attributes',$xml_elem)) {  
		     list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);  
		} else {  
		     $level[$xml_elem['level']] = $xml_elem['tag'];  
		}  
		 }  
		 if ($xml_elem['type'] == 'complete') {  
		$start_level = 1;  
		$php_stmt = '$params';  
		while($start_level < $xml_elem['level']) {  
		     $php_stmt .= '[$level['.$start_level.']]';  
		     $start_level++;  
		}  
		$php_stmt .= '[$xml_elem[\'tag\']] = @$xml_elem[\'value\'];'; 
		//echo $php_stmt;
		eval($php_stmt);  
		 }  
		}  
		curl_close($ch);  
		return $params['RATINGSERVICESELECTIONRESPONSE']['RATEDSHIPMENT']['TOTALCHARGES']['MONETARYVALUE'];  
	    }  
    }