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
 * An action to checkout and pay.
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created December 2007
 */
class actions_checkout {

	/**
	 * Implements action handle() method
	 */
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