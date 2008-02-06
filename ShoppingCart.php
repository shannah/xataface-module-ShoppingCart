<?php

class modules_ShoppingCart {


	public function __construct(){
		$this->createInvoiceTable();
	}

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