<?php
class actions_invoices {
	function handle(&$params){
		$auth =& Dataface_AuthenticationTool::getInstance();
		$user =& $auth->getLoggedInUser();
		if ( !$user ){
			return Dataface_Error::permissionDenied('You must be logged in to see your invoices');
		}
		$invoices = df_get_records_array('dataface__invoices', array('username'=>'='.$auth->getLoggedInUsername()));
		
		df_register_skin('ShoppingCart', dirname(__FILE__).'/../templates');
		df_display(array('invoices'=>$invoices), 'ShoppingCart/invoices.html');
	}
}