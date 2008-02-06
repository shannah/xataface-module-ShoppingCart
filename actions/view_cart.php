<?php
class actions_view_cart {
	function handle(&$params){
		import('modules/ShoppingCart/lib/ShoppingCart/ShoppingCart.class.php');
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		
		$checkout = $cart->displayCheckout();
		
		df_register_skin('cart', 'modules/ShoppingCart/templates');
		
		df_display(
			array(
				'cart' => $checkout
			), 
			'ShoppingCart/view_cart.html'
			);
		
	}
}