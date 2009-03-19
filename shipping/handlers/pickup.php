<?php
class modules_ShoppingCart_shipping_handlers_pickup {

	function getInfo(){
		$info = array();
		$info[] = array(
			'shipping_method_name' => 'PICKUP',
			'shipping_method_label' => 'Pick up (No delivery necessary)'
		);
		
		return $info;
	}
	
	
	
	
	
	function calculateShipping($method, $params){
		$scTool = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart');
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		
		$item = ShoppingCartFactory::getFactory()->newItem();
		$item->productID = 'PICKUP_SHIPPING';
		$item->description = $params['shipping_method_label'];
		$item->unitPrice = 0.0;
		$cart->shipping = $item;
		$cart->save();
		return true;
	}
	
	
	
	
}