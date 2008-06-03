<?php
class actions_refresh_shipping_methods {
	function handle(&$params){
	
		$res = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart')->refreshShippingMethods();
		
		if ( PEAR::isError($res) ) return $res;
		
		echo "Refresh Complete";
	}
}