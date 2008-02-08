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


	/**
	 * Constructor, creates the invoice table if it doesn't exist already.
	 */
	public function __construct(){
		$this->createInvoiceTable();
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
			`address1` varchar(100) default null,
			`address2` varchar(100) default null,
			`city` varchar(40) default null,
			`province` varchar(2) default null,
			`country` varchar(2) default null,
			`postalCode` varchar(32) default null,
			`phone` varchar(32) default null,
			`email` varchar(127) default null,
			
			`data` text )";
		
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
		} else {
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
	
	
	
	

}