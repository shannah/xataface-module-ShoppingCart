<?php
class actions_payment_complete {
	function handle(&$params){
		df_register_skin('ShoppingCart', dirname(__FILE__).'/../templates');
		df_display(array(), 'ShoppingCart/payment_complete.html');
	
	}
}