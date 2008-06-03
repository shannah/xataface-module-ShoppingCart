<?php
class modules_ShoppingCart_shipping_handlers_fedex {

	function getInfo(){
		$info = array();
		$info[] = array(
			'shipping_method_name' => 'FEDEX_PRIORITYOVERNIGHT',
			'shipping_method_label' => 'FedEx Priority Overnight'
		);
		return $info;
	}
	
	function calculate_FEDEX_PRIORITYOVERNIGHT($params){
		$params['service_name'] = 'PRIORITYOVERNIGHT';
		$params['service_label'] = 'FedEx Priority Overnight';
		
		
		import('modules/ShoppingCart/lib/ShoppingCart/ShoppingCart.class.php');
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		
		import('Dataface/Ontology.php');
		Dataface_Ontology::registerType('InventoryItem', 'modules/ShoppingCart/Ontology/InventoryItem.php', 'modules_ShoppingCart_Ontology_InventoryItem');
		$ontology = Dataface_Ontology::newOntology('InventoryItem', $record->_table->tablename);
			
		
		
		$item = ShoppingCartFactory::getFactory()->newItem();
		$item->productID = 'FEDEX_PRIORITYOVERNIGHT';
		$item->description = $params['service_label'];
		
		$weight = 0.0;
		foreach ( $cart->items as $citem ){
			$product = df_get_record_by_id($citem->productID);
			if ( !$product ){ 
				return PEAR::raiseError("Could not calculate shipping because product ".$citem->productID." could not be loaded.");
			}
			
			
			
			$inventoryItem = $ontology->newIndividual($product);
			
			$weight += floatval($inventoryItem->val('weight'));
			
		}
		
		$params['weight'] = $weight;
		$item->unitPrice = $this->getPrice($params);
		
		$cart->shipping = $item;
		$cart->save();
		return true;
		
	}
	
	
	function getPrice($params = array()){
		$fedex = new Fedex;
		$fedex->setServer("https://gatewaybeta.fedex.com/GatewayDC");
		$fedex->setAccountNumber(123123123); //Get your own - this will not work...
		$fedex->setMeterNumber(12312312);    //Get your own - this will not work...
		$fedex->setCarrierCode("FDXE");
		$fedex->setDropoffType("REGULARPICKUP");
		$fedex->setService($params['service_name'], $params['service_label']);
		$fedex->setPackaging("YOURPACKAGING");
		$fedex->setWeightUnits("LBS");
		$fedex->setWeight($params['weight']);
		$fedex->setOriginStateOrProvinceCode($params['fedex.orig_province_code']);
		$fedex->setOriginPostalCode($params['fedex.orig_postalCode']);
		$fedex->setOriginCountryCode($params['fedex.orig_country_code']);
		$fedex->setDestStateOrProvinceCode($params['province_code']);
		$fedex->setDestPostalCode($params['postalCode']);
		$fedex->setDestCountryCode($params['country_code']);
		$fedex->setPayorType("SENDER");
		
		$price = $fedex->getPrice();
		return $price->price->rate;
	}
	
}