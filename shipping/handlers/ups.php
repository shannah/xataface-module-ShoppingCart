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
	
	function calculate($params=array()){
		
		$scTool = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart');
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		
		$weight = $scTool->getTotalWeight();
		print_r($params);
		$price = $this->getUPSprice(
			$params['shipType'],
			$params['orig_postalCode'],
			$params['postalCode'],
			$params['country'],
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
	
}