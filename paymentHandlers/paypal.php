<?php
/**
 * A payment handler for the Paypal payment method.  This handler is used by the
 * various shopping cart actions to handle requests specific to paypal.
 * In particular this handler has functionality to checkout and to validate 
 * IPN (instant payment notification).
 */
class Dataface_modules_ShoppingCart_paymentHandlers_paypal {
	
	function checkout(Dataface_Record $invoice, $params=array()){
	
		$cart = unserialize($invoice->val('data'));
		$action =& $params['action'];
		
		if ( !isset($action['paypal.account']) ){
			return PEAR::raiseError("No paypal account is specified in the conf.ini file.");
		}
		
		if ( !isset($action['currency_code']) ){
			$action['currency_code'] = 'USD';
		}
		
		require_once DATAFACE_PATH.'/modules/ShoppingCart/lib/paypal.class.php';
		
		$p = new paypal_class;
		
		$p->add_field('business', $action['paypal.account']);
		$p->add_field('cmd', '_cart');
		$p->add_field('upload','1');
		$p->add_field('currency_code', $action['currency_code']);
		
		$i = 0;
		foreach ( $cart->items as $item ){
			$i++;
			
			$p->add_field('tax_'.$i, $item->tax($cart->taxPercents));
			$p->add_field('amount_'.$i, $item->unitPrice);
			$p->add_field('quantity_'.$i, $item->quantity);
			$p->add_field('item_name_'.$i, $item->description);
			$p->add_field('item_number_'.$i, $item->productID);
		}
		
		if ( $invoice->val('address1') ) $p->add_field('address1', $invoice->val('address1'));
		if ( $invoice->val('address2') ) $p->add_field('address2', $invoice->val('address2'));
		if ( $invoice->val('city') ) $p->add_field('city', $invoice->val('city'));
		if ( $invoice->val('province') ) $p->add_field('state', $invoice->val('state'));
		if ( $invoice->val('country') ) $p->add_field('country', $invoice->val('country'));
		if ( $invoice->val('phone') ) $p->add_field('night_phone_a', $invoice->val('phone'));
		if ( $invoice->val('firstName') ) $p->add_field('first_name', $invoice->val('firstName'));
		if ( $invoice->val('lastName') ) $p->add_field('last_name', $invoice->val('lastName'));
		if ( $invoice->val('email') ) $p->add_field('email', $invoice->val('email'));
		
		$p->add_field('notify_url', $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?-action=paypal_ipn');
		$p->add_field('invoice', $invoice->val('invoiceID'));
		$p->add_field('return', $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?-action=payment_complete');
		$p->add_field('cancel_return', $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?-action=payment_cancelled');
		
		
		$p->submit_paypal_post();
		
	}

}