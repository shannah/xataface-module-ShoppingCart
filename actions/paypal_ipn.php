<?php
class actions_paypal_ipn {
	function handle(&$params){
		require_once DATAFACE_PATH.'/modules/ShoppingCart/lib/paypal.class.php';
		
		$p = new paypal_class;
		if ( $p->validate_ipn() ){
			if ( !isset($p->ipn_data['invoice']) ){
				error_log('Failed to validate invoice for payment because paypal did not specify an invoice in its ipn data');
				exit;
			}
			
			$invoiceID = $p->ipn_data['invoice'];
			$invoice = df_get_record('dataface__invoices', array('invoiceID'=>$invoiceID));
			if ( !$invoice ){
				error_log('Failed to validate invoice for payment of invoice id '.$invoiceID.' because the invoice does not exist.');
				exit;
			}
			
			$invoice->setValue('status', 'PAID');
			$invoice->setValue('dateModified', date('Y-m-d H:i:s'));
			$invoice->save();
			
		}
	}

}