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
 * An action to add an item to the shopping cart.
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created December 2007
 */
class actions_add_to_cart {

	/**
	 * Implements action handle() method.
	 */
	function handle(&$params){
		$action =& $params['action'];
		
		import('modules/ShoppingCart/lib/ShoppingCart/ShoppingCart.class.php');
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		
		
		
		$item = ShoppingCartFactory::getFactory()->newItem();
		
		$app =& Dataface_Application::getInstance();
		if ( !isset($_POST['--record_id']) ) return PEAR::raiseError("No record id specified");
		$record = df_get_record_by_id($_POST['--record_id']);
		
		if ( !$record ) return PEAR::raiseError("The record '".$_POST['--record_id']."' could not be found.");
		
		$existingItem = $cart->getItemByProductID($record->getId());
		if ( $existingItem ){
			$existingItem->quantity++;
		} else {
			
			import('Dataface/Ontology.php');
			Dataface_Ontology::registerType('InventoryItem', 'modules/ShoppingCart/Ontology/InventoryItem.php', 'modules_ShoppingCart_Ontology_InventoryItem');
			$ontology = Dataface_Ontology::newOntology('InventoryItem', $record->_table->tablename);
			
			$inventoryItem = $ontology->newIndividual($record);
			
			
			$item->productID = $record->getId();
			$item->description = $inventoryItem->strval('description');
			if ( !isset($item->description) ) $item->description = $record->getTitle();
			if ( @$_POST['--quantity'] ) $item->quantity = intval($_POST['--quantity']);
			$item->unitPrice = $inventoryItem->val('unitPrice');
			if ( $record->_table->hasField( $ontology->getFieldname('taxes') ) ){
				$item->taxes = $inventoryItem->val('taxes');
			} else {
				$taxes = preg_grep('/^taxes\./', array_keys($action) );
				foreach ( $taxes as $taxname ){
					list($dump, $taxname) = explode('.', $taxname);
					$item->taxes[$taxname] = true;
				}	
			}
			
			$cart->addItem($item);
		}
		$cart->save();
		
		if ( isset( $_POST['--redirect'] ) ) $link = $_POST['--redirect'];
		else if ( isset( $_SERVER['HTTP_REFERER'] ) ) $link = $_SERVER['HTTP_REFERER'];
		
		if ( strpos($link, '?') === false ) $link .= '?';
		$link .= '&--msg=The item was successfully added to the cart';
		header('Location: '.$link);
		exit;
	}

}