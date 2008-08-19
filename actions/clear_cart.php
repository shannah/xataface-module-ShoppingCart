<?php
class actions_clear_cart {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		$cart->emptyCart();
		if ( $cart->invoiceID ){
			$scTool = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart');
			$invoice = $scTool->createInvoice(null);
			import('Dataface/IO.php');
			$io =& new Dataface_IO('dataface__invoices');
			$io->delete($invoice);
			//$invoice->delete();
		}
		
		header('Location: '.$app->url('-action=view_cart'));
		exit;
	}
}