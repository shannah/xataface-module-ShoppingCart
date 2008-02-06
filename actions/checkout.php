<?php
class actions_checkout {
	function handle(&$params){
		if ( isset($_POST['--payment-method']) ) $paymentMethod = $_POST['--payment-method'];
		else $paymentMethod = 'paypal';
		

		$invoice = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart')->createInvoice($paymentMethod);
		if ( PEAR::isError($invoice) ) return $invoice;

		require_once DATAFACE_PATH.'/modules/ShoppingCart/paymentHandlers/'.basename($paymentMethod).'.php';
		$handlerClass = 'Dataface_modules_ShoppingCart_paymentHandlers_'.$paymentMethod;
		$handler = new $handlerClass;
		return $handler->checkout($invoice, $params);
		
		
		
		
	}
}