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
 * A module that adds shopping cart functionality to any Dataface application.
 * Any record that can be handled by the InventoryItem ontology can be added to
 * the cart and purchased.  
 *
 * The initial release will include a paypal payment handler, but an api will
 * be included to develop other payment handlers also.
 *
 * Requirements:
 * -------------
 *
 * PHP 5+
 * Xataface 1.0+
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created December 2007
 *
 * Development status:
 *	Incomplete - under development.
 */
class modules_ShoppingCart {

	public $shippingMethod = null;


	/**
	 * Constructor, creates the invoice table if it doesn't exist already.
	 */
	public function __construct(){
		if ( !@$_SESSION['modules_ShoppingCart.tables_created'] or @$_REQUEST['--create-shopping-tables']){
			$this->createInvoiceTable();
			$this->createShippingTable();
			$_SESSION['modules_ShoppingCart.tables_created'] = true;
		} 
	}

	/**
	 * A block (sample) that adds an "Add to cart" form in the left 
	 * column of each page.
	 */
	function block__after_left_column(){
		
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		if ( $record and $record->_table->implementsOntology('InventoryItem') ){
			$record_id = htmlspecialchars($record->getId());
			echo <<<END
			<div>
				<form method="post" action="{$_SERVER['PHP_SELF']}">
					<input type="hidden" name="-action" value="add_to_cart" />
					Qty: <input type="text" size="2" name="--quantity" value="1" />
					<input type="hidden" name="--record_id" value="$record_id" />
					<input type="submit" name="submit" value="Add to Cart" />
				</form>
			</div>
END;
		}
	}
	
	
	
	
	/**
	 * Creates a table to store invoices.
	 */
	function createInvoiceTable(){
		$sql = "create table if not exists `dataface__invoices` (
			invoiceID int(11) not null auto_increment primary key,
			dateCreated datetime default null,
			dateModified datetime default null,
			`status` enum('PENDING','PAID','APPROVED') not null default 'PENDING',
			`amount` decimal(10,2) not null,
			`paymentMethod` varchar(32) not null,
			`referenceID` varchar(64) default null,
			`username` varchar(32) default null,
			`firstName` varchar(32) default null,
			`lastName` varchar(32) default null,
			`address_name` varchar(100) default null,
			`address1` varchar(100) default null,
			`address2` varchar(100) default null,
			`city` varchar(40) default null,
			`province` varchar(2) default null,
			`country` varchar(2) default null,
			`postalCode` varchar(32) default null,
			`shipping_method` varchar(50) default null,
			`phone` varchar(32) default null,
			`email` varchar(127) default null,
			
			`data` text )";
		
		$res = mysql_query($sql, df_db());
		if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
			
	}
	
	function createShippingTable(){
	
		$sql = "create table if not exists `dataface__shipping_methods` (
			shipping_method_id int(11) not null auto_increment primary key,
			shipping_method_name varchar(50) not null,
			shipping_method_label varchar(100),
			shipping_method_enabled tinyint(1) default 1,
			shipping_method_module varchar(32) not null

			)";
		$res = mysql_query($sql, df_db());
		if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
			
	}
	
	/**
	 * Creates an invoice for a given payment method.
	 * @param string $paymentMethod The method of payment.
	 * @return Dataface_Record The invoice record - a record of the invoices table.
	 *
	 */
	function createInvoice($paymentMethod){
		$cart = ShoppingCartFactory::getFactory()->loadCart();

		if ( isset($cart->invoiceID) ){
			$invoice = df_get_record('dataface__invoices', array('invoiceID'=>$cart->invoiceID));
			
			
			if (PEAR::isError($invoice) ){
				return $invoice;
			}
			
			if ( $invoice->username and $invoice->username != Dataface_AuthenticationTool::getInstance()->getLoggedInUsername() ){
				return PEAR::raiseError("Cannot modify invoice because username does not match invoice owner");
			}
		} 
		if (!$invoice ){
			$invoice = new Dataface_Record('dataface__invoices');
			$invoice->setValue('dateCreated', date('Y-m-d H:i:s'));
			$invoice->setValue('dateModified', date('Y-m-d H:i:s'));
			$invoice->setValue('status', 'PENDING');
			$invoice->setValue('amount', $cart->total());
			$invoice->setValue('paymentMethod', $paymentMethod);
			$invoice->setValue('username', Dataface_AuthenticationTool::getInstance()->getLoggedInUsername());
			$invoice->save();
			
			// Now that the invoice is saved, we can set its invoice id to the cart
			$cart->invoiceID = $invoice->getValue('invoiceID');
			$invoice->setValue('data', serialize($cart));
			$cart->save();
			$invoice->save();
		
		}
		if ( !$invoice ){
			return PEAR::raiseError("Failed to create invoice");
		}
		
		
		
		$invoice->setValue('paymentMethod', $paymentMethod);
		$invoice->setValue('dateModified', date('Y-m-d H:i:s'));
		$invoice->setValue('amount', $cart->total());
		
		$invoice->save();
		return $invoice;
		
	}
	
	/**
	 * Returns an array of Dataface_Record objects from the dataface__shipping_methods
	 * table.
	 */
	function getShippingMethods($query=array()){
		return df_get_records_array('dataface__shipping_methods', $query);
	}
	
	/**
	 * Returns the currently selected shipping method.
	 *
	 * @return Dataface_Record From dataface__shipping_methods table.
	 */
	function getShippingMethod(){
		if ( !isset($this->shippingMethod) ){
			$cart = ShoppingCartFactory::getFactory()->loadCart();
			$methodName = $cart->shippingMethod;
			$query = array('shipping_method_enabled'=>1,'-limit'=>1);
			if ( $methodName ){
				$query['shipping_method_name'] = $methodName;
			}
			$methods = $this->getShippingMethods($query);
			if ( $methods ) $this->setShippingMethod($methods[0]);
		}
		return $this->shippingMethod;
	}
	
	/**
	 * Sets the current shipping method to the method specified by $methodID
	 * @param mixed $methodID Either the ID of a shipping method, or a Dataface_Record
	 *		object from the dataface__shipping_methods table.
	 */
	function setShippingMethod($methodName){
		if ( is_a($methodName, 'Dataface_Record') ){
			unset($this->shippingMethod);
			$this->shippingMethod = $methodName;
			return;
		}
		$currentMethod = $this->getShippingMethod();
		if ( $currentMethod and $currentMethod->val('shipping_method_name') == $methodName ){
			return;
		}
		
		unset($this->shippingMethod);
		$methods = $this->getShippingMethods(array('shipping_method_name' => $methodName, 'shipping_method_enabled' =>1, '-limit'=>1));
		if ( $methods ){
			$this->shippingMethod = $methods[0];
			$cart = ShoppingCartFactory::getFactory()->loadCart();
			$cart->shippingMethod = $this->shippingMethod->val('shipping_method_name');
			$cart->save();
		} else {
			return PEAR::raiseError("The shipping method with id $methodID could not be applied because either it does not exist or it is not enabled");
		}
	}
	
	
	/**
	 * Refreshes the shipping methods based on the handlers that are on the file system
	 * in the shipping/handlers directory.
	 */
	function refreshShippingMethods(){
		$files = scandir(dirname(__FILE__).'/shipping/handlers');
		$existing = $this->getShippingMethods();
		$existingNames = array();
		foreach ($existing as $handler ){
			$existingNames[$handler->val('shipping_handler_name')] = $handler;
			unset($handler);
		}
		
		foreach ($files as $file){
			if ( preg_match('/\.php$/', $file) ){
				list($name) = explode('.', $file);
				$name = basename($name);
				import('modules/ShoppingCart/shipping/handlers/'.basename($file));
				$classname = 'modules_ShoppingCart_shipping_handlers_'.$name;
				$handlerObject = new $classname;
				if ( isset($existingNames[$name]) ){
					$handlerDesc = $existingNames[$name];
				} else {
					$handlerDesc = new Dataface_Record('dataface__shipping_methods', array());
				}
				
				if ( method_exists($handlerObject, 'getInfo') ){
					$handlerInfo = $handlerObject->getInfo();
				} else {
					$handlerInfo = array();
						
				}
				if ( !$handlerInfo) {
					$defaultHandler = array(
						'shipping_method_name' => $name,
						'shipping_method_label' => ucfirst($name)
					);
					$handlerInfo[] = $defaultHandler;
				}
				
				foreach ( $handlerInfo as $key => $handler ){
					if ( !isset($handler['shipping_method_label']) ){
						$handlerInfo[$key]['shipping_method_label'] = ucfirst($handlerInfo[$key]['shipping_method_name']);
						
					}
					
					$handler['shipping_method_module'] = $name;
					
					$method_name = $handler['shipping_method_name'];
					
					
					if ( isset($existingNames[$method_name]) ){
						$handlerDesc = $existingNames[$method_name];
					} else {
						$handlerDesc = new Dataface_Record('dataface__shipping_methods', array());
					}
					
					$handlerDesc->setValues($handler);
					$handlerDesc->save();
					unset($handlerDesc);
					
				}
				
				
				
				unset($handlerInfo);
				unset($handlerObject);
				unset($classname);
				unset($name);
				
				
				
			}
		}
	}
	
	
	/**
	 * Returns a callback that can be used to calculate the shipping for the current
	 * handler.
	 *
	 * @example
	 * <code>
	 * $handler = $this->getShippingHandler();
	 * call_user_func($handler);
	 * </code>
	 */
	function getShippingHandler(){
		$method = $this->getShippingMethod();
		if ( !$method ) return null;
		
		$module = $method->val('shipping_method_module');
		import('modules/ShoppingCart/shipping/handlers/'.basename($module).'.php');
		$classname = 'modules_ShoppingCart_shipping_handlers_'.basename($module);
		$handler = new $classname;
		$funcName = 'calculate_'.$method->val('shipping_method_name');
		return array($handler, $funcName);
	}
	
	
	function getTotalWeight(){
		import('modules/ShoppingCart/lib/ShoppingCart/ShoppingCart.class.php');
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		
		import('Dataface/Ontology.php');
		Dataface_Ontology::registerType('InventoryItem', 'modules/ShoppingCart/Ontology/InventoryItem.php', 'modules_ShoppingCart_Ontology_InventoryItem');
		
		
		
		$weight = 0.0;
		foreach ( $cart->items as $citem ){
			$product = df_get_record_by_id($citem->productID);
			
			if ( !$product ){ 
				return PEAR::raiseError("Could not calculate shipping because product ".$citem->productID." could not be loaded.");
			}
			
			$ontology = Dataface_Ontology::newOntology('InventoryItem', $product->_table->tablename);
			
			$inventoryItem = $ontology->newIndividual($product);
			
			$weight += floatval($inventoryItem->val('weight')*$citem->quantity);
			
			unset($inventoryItem);
			unset($citem);
			unset($ontology);
		}
		
		return $weight;
	}
	
	
	

}