<?php
class modules_ShoppingCart_shipping_handlers_ups {

	function getInfo(){
		$info = array();
		$info[] = array(
			'shipping_method_name' => 'UPS_1DM',
			'shipping_method_label' => 'UPS Next Day Early AM'
		);
		$info[] = array(
			'shipping_method_name' => 'UPS_1DA',
			'shipping_method_label' => 'UPS Next Day Air'
		);
		
		$info[] = array(
			'shipping_method_name' => 'UPS_1DP',
			'shipping_method_label' => 'UPS Next Day Air Saver'
		);
		
		$info[] = array(
			'shipping_method_name' => 'UPS_2DM',
			'shipping_method_label' => 'UPS 2nd Day Air AM'
		);
		
		$info[] = array(
			'shipping_method_name' => 'UPS_2DA',
			'shipping_method_label' => 'UPS 2nd Day Air'
		);
		
		$info[] = array(
			'shipping_method_name' => 'UPS_3DS',
			'shipping_method_label' => 'UPS 3 Day Select'
		);
		
		$info[] = array(
			'shipping_method_name' => 'UPS_GND',
			'shipping_method_label' => 'UPS Ground'
		);
		
		$info[] = array(
			'shipping_method_name' => 'UPS_STD',
			'shipping_method_label' => 'UPS Canada Standard'
		);
		
		$info[] = array(
			'shipping_method_name' => 'UPS_XPR',
			'shipping_method_label' => 'UPS Worldwide Express'
		);
		
		$info[] = array(
			'shipping_method_name' => 'UPS_XPD',
			'shipping_method_label' => 'UPS Worldwide Express Expedited'
		);
		
		$info[] = array(
			'shipping_method_name' => 'UPS_WXS',
			'shipping_method_label' => 'UPS Worldwide Saver'
		);
		return $info;
	}
	
	
	
	
	function calculate_UPS_1DM($params){
		$params['shipType'] = '1DM';
		return $this->calculate($params);
		
	}
	
	function calculate_UPS_STD($params){
		$params['shipType'] = 'STD';
		return $this->calculate($params);
	}
	
	function calculateShipping($method, $params){
		switch($method){
			case 'UPS_1DM':
				return $this->calculate_UPS_1DM($params);
				
			case 'UPS_STD':
				return $this->calculate_UPS_STD($params);
		}
	}
	
	function calculate($params=array()){
		
		$scTool = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart');
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		
		$weight = $scTool->getTotalWeight();
		print_r($params);
		$price = $this->getUPSprice(
			$params['shipType'],
			$params['source.postalCode'],
			$params['dest.postalCode'],
			$params['dest.country'],
			$weight
		);
		
		$item = ShoppingCartFactory::getFactory()->newItem();
		$item->productID = 'UPS_'.$params['shipType'];
		$item->description = $params['shipping_method_label'];
		$item->unitPrice = $price;
		$cart->shipping = $item;
		$cart->save();
		return true;
	}
	
	
	function getUPSprice ($shipType,$sendZipcode,$recieveZipcode,$recieveCountry,$weight) {
		$tmp = "AppVersion=1.2&AcceptUPSLicenseAgreement=yes&ResponseType=application/x-ups-rss&ActionCode=3".
			"&RateChart=Regular+Daily+Pickup&DCISInd=0&SNDestinationInd1=0&SNDestinationInd2=0&ResidentialInd=0&PackagingType=00".
			"&ServiceLevelCode=".urlencode($shipType)."&ShipperPostalCode=".urlencode($sendZipcode)."&ConsigneePostalCode=". urlencode($recieveZipcode).
			"&ConsigneeCountry=".urlencode($recieveCountry)."&PackageActualWeight=.".urlencode($weight)."&DeclaredValueInsurance=0\n\r";
				
		$request = "POST /using/services/rave/qcost_dss.cgi HTTP/1.0\nContent-type: application/x-www-form-urlencoded\nContent-length: " .
			strlen($tmp) . "\n\n" . $tmp;
					
		$this->socket = fsockopen("www.ups.com", 80);
		fputs($this->socket, $request);	

		//echo fread ($this->socket, 4096);
		//		echo "About to get price";exit;
		strtok(fread ($this->socket, 4096), "%");
	
		for ($i = 0; $i < 12 ;$i++)
			$price = strtok("%");
	
		fclose($this->socket);
		
		return($price);
	}
	
	
	function _upsGetQuote($params=array()) {

        // Create the access request
        $accessRequestHeader =
        "<?xml version=\"1.0\"?>\n".
        "<AccessRequest xml:lang=\"en-US\">\n".
        "   <AccessLicenseNumber>". $params['ups.access_key'] ."</AccessLicenseNumber>\n".
        "   <UserId>". $params['ups.access_username'] ."</UserId>\n".
        "   <Password>". $params['ups.access_password'] ."</Password>\n".
        "</AccessRequest>\n";

        $ratingServiceSelectionRequestHeader =
        "<?xml version=\"1.0\"?>\n".
        "<RatingServiceSelectionRequest xml:lang=\"en-US\">\n".
        "   <Request>\n".
        "       <TransactionReference>\n".
        "           <CustomerContext>Rating and Service</CustomerContext>\n".
        "           <XpciVersion>". '1.0001'/*$this->xpci_version*/ ."</XpciVersion>\n".
        "       </TransactionReference>\n".
        "       <RequestAction>Rate</RequestAction>\n".
        "       <RequestOption>shop</RequestOption>\n".
        "   </Request>\n";
        // according to UPS the CustomerClassification and PickupType containers should
        // not be present when the origin country is non-US see:
        // http://forums.oscommerce.com/index.php?s=&showtopic=49382&view=findpost&p=730947
        if ($params['source.country']  == 'US') {
        $ratingServiceSelectionRequestHeader .=
        "   <PickupType>\n".
        "       <Code>". $params['shipType'] ."</Code>\n".
        "   </PickupType>\n";
        //"   <CustomerClassification>\n".
        //"       <Code>". $this->customer_classification ."</Code>\n".
        //"   </CustomerClassification>\n";
        }
        $ratingServiceSelectionRequestHeader .=
        "   <Shipment>\n".
        "       <Shipper>\n";
        if ($this->use_negotiated_rates == 'True') {
        $ratingServiceSelectionRequestHeader .=
        "         <ShipperNumber>" . $this->access_account_number . "</ShipperNumber>\n";
        }
        $ratingServiceSelectionRequestHeader .=
        "           <Address>\n".
        //"               <City>". $params['source.city'] ."</City>\n".
        "               <StateProvinceCode>". $params['source.province'] ."</StateProvinceCode>\n".
        "               <CountryCode>". $params['source.country'] ."</CountryCode>\n".
        "               <PostalCode>". $params['source.postalCode'] ."</PostalCode>\n".
        "           </Address>\n".
        "       </Shipper>\n".
        "       <ShipTo>\n".
        "           <Address>\n".
        //"               <City>". $params['dest.city'] ."</City>\n".
        "               <StateProvinceCode>". $params['dest.province'] ."</StateProvinceCode>\n".
        "               <CountryCode>". $params['dest.country'] ."</CountryCode>\n".
        "               <PostalCode>". $parmas['dest.postalCode'] ."</PostalCode>\n".
        //($this->quote_type == "Residential" ? "<ResidentialAddressIndicator/>\n" : "") .
        "           </Address>\n".
        "       </ShipTo>\n";
        for ($i = 0; $i < $this->items_qty; $i++) {

            $ratingServiceSelectionRequestPackageContent .=
            "       <Package>\n".
            "           <PackagingType>\n".
            "               <Code>". $this->package_types[$this->package_type] ."</Code>\n".
            "           </PackagingType>\n";
            if ($this->dimensions_support > 0 && ($this->item_length[$i] > 0 ) && ($this->item_width[$i] > 0 ) && ($this->item_height[$i] > 0)) {

                $ratingServiceSelectionRequestPackageContent .=
                "           <Dimensions>\n".
                "               <UnitOfMeasurement>\n".
                "                   <Code>". $this->unit_length ."</Code>\n".
                "               </UnitOfMeasurement>\n".
                "               <Length>". $this->item_length[$i] ."</Length>\n".
                "               <Width>". $this->item_width[$i] ."</Width>\n".
                "               <Height>". $this->item_height[$i] ."</Height>\n".
                "           </Dimensions>\n";
            }

            $ratingServiceSelectionRequestPackageContent .=
            "           <PackageWeight>\n".
            "               <UnitOfMeasurement>\n".
            "                   <Code>". $this->unit_weight ."</Code>\n".
            "               </UnitOfMeasurement>\n".
            "               <Weight>". $this->item_weight[$i] ."</Weight>\n".
            "           </PackageWeight>\n".
            "           <PackageServiceOptions>\n".
            //"               <COD>\n".
            //"                   <CODFundsCode>0</CODFundsCode>\n".
            //"                   <CODCode>3</CODCode>\n".
            //"                   <CODAmount>\n".
            //"                       <CurrencyCode>USD</CurrencyCode>\n".
            //"                       <MonetaryValue>1000</MonetaryValue>\n".
            //"                   </CODAmount>\n".
            //"               </COD>\n".
            "               <InsuredValue>\n".
            "                   <CurrencyCode>".MODULE_SHIPPING_UPSXML_CURRENCY_CODE."</CurrencyCode>\n".
            "                   <MonetaryValue>".$this->item_price[$i]."</MonetaryValue>\n".
            "               </InsuredValue>\n".
            "           </PackageServiceOptions>\n".
            "       </Package>\n";
        }

        $ratingServiceSelectionRequestFooter = '';
        //"   <ShipmentServiceOptions/>\n".
           if ($this->use_negotiated_rates == 'True') {
        $ratingServiceSelectionRequestFooter .=
            "       <RateInformation>\n".
            "         <NegotiatedRatesIndicator/>\n".
            "       </RateInformation>\n";
           }
        $ratingServiceSelectionRequestFooter .=
        "   </Shipment>\n";
        // according to UPS the CustomerClassification and PickupType containers should
        // not be present when the origin country is non-US see:
        // http://forums.oscommerce.com/index.php?s=&showtopic=49382&view=findpost&p=730947
        if ($this->origin_country == 'US') {
        $ratingServiceSelectionRequestFooter .=
              "   <CustomerClassification>\n".
              "       <Code>". $this->customer_classification ."</Code>\n".
              "   </CustomerClassification>\n";
        }
        $ratingServiceSelectionRequestFooter .=
        "</RatingServiceSelectionRequest>\n";

        $xmlRequest = $accessRequestHeader .
        $ratingServiceSelectionRequestHeader .
        $ratingServiceSelectionRequestPackageContent .
        $ratingServiceSelectionRequestFooter;

        //post request $strXML;
        $xmlResult = $this->_post($this->protocol, $this->host, $this->port, $this->path, $this->version, $this->timeout, $xmlRequest);
        // BOF testing with a response from UPS saved as a text file
        // needs commenting out the line above: $xmlResult = $this->_post($this->protocol, etcetera
/*        $filename = '/srv/www/htdocs/catalog/includes/modules/shipping/example_response.xml';
        $fp = fopen($filename, "r") or die("couldn't open file");
        $xmlResult = "";
        while (! feof($fp)) {
          $xmlResult .= fgets($fp, 1024);
        } 
        // EOF testing with a text file */
        return $this->_parseResult($xmlResult);
    }
	
}