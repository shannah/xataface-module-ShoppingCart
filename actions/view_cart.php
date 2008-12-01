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
		$app =& Dataface_Application::getInstance();
		if ( isset($app->_conf['ShoppingCart_taxes']) ){
			$cart->taxPercents = $app->_conf['ShoppingCart_taxes'];
		}
		
		if ( $app->prefs['ShoppingCart_disableShipping'] ){
			$cart->shippingEnabled = false;
		}
		$cart->save();
		$scTool = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart');
		$invoice = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart')->createInvoice($paymentMethod);
		if ( PEAR::isError($invoice) ) return $invoice;
		
		$shipping = $scTool->getShippingMethod();
		$destination = array();
		if ( $invoice->val('country') ) $destination[] = $invoice->val('country');
		if ( $invoice->val('province') ) $destination[] = $invoice->val('province');
		if ( $invoice->val('postalCode') ) $destination[] = $invoice->val('postalCode');
		$destination = implode(' / ', $destination);
		//if ( !$shipping ) $shipping = null;
		//else $shipping = df_get_record_by_id($shipping[0]->productID);
		
		//$shippingMethods = df_get_records_array('dataface__shipping_methods', array('shipping_method_enabled'=>1));
		
	
		$checkout = $cart->displayCheckout();
		
		df_register_skin('cart', 'modules/ShoppingCart/templates');
		
		df_display(
			array(
				'cartObject'=>$cart,
				'cart' => $checkout,
				//'shippingMethods' => $shippingMethods,
				'currentShippingMethod' => $shipping,
				'invoice' => $invoice,
				'destination' => $destination
			), 
			'ShoppingCart/view_cart.html'
			);
		
	}
}