<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
 
/**
 * An action to view the contents of the shopping cart.
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created December 2007
 */
class actions_view_cart {

	/**
	 * Implements action handle() method.
	 */
	function handle(&$params){
		import('modules/ShoppingCart/lib/ShoppingCart/ShoppingCart.class.php');
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		
		
		$invoice = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart')->createInvoice($paymentMethod);
		if ( PEAR::isError($invoice) ) return $invoice;
		
		$shipping = $cart->getItemsByCategory('shipping');
		if ( !$shipping ) $shipping = null;
		else $shipping = df_get_record_by_id($shipping[0]->productID);
		
		$shippingMethods = df_get_records('dataface__shipping_methods', array('shipping_method_enabled'=>1));
		
		
		$checkout = $cart->displayCheckout();
		
		df_register_skin('cart', 'modules/ShoppingCart/templates');
		
		df_display(
			array(
				'cart' => $checkout,
				'shippingMethods' => $shippingMethods,
				'currentShippingMethod' => $shipping,
				'invoice' => $invoice
			), 
			'ShoppingCart/view_cart.html'
			);
		
	}
}