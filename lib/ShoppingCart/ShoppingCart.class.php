<?php
define('ShoppingCart_key', 'ShoppingCart');

/**
 * A simple shopping cart class that binds to the session.  It allows you 
 * to add items to the cart, calculate totals, taxes, etc.
 *
 * @example
 * <code>
 * $cart = ShoppingCart::load();
 * $item = new ShoppingCartItem;
 * $item->description = "Box of baseball cards";
 * $item->unitPrice = 24.99;
 * $item ->quantity = 2;
 * $item->taxes['gst'] = true;
 * $item->taxes['pst'] = true;
 *
 * // Add item to the cart.
 * $cart->addItem($item);
 *
 * // Now save the cart to the session
 * $cart->save();
 * </code>
 */
class ShoppingCart {
	/**
	 * @var int The shopping cart id.
	 */
	var $id = 0;
	
	/**
	 * @var array(ShoppingCartItem) The list of items in this cart.
	 */
	var $items = array();
	
	/**
	 * @var array( string => float) The tax percentages that should be charged
	 *		for each tax.
	 * key: The name of the tax (e.g. GST).
	 * value: The percent of the tax as a decimal (e.g. 0.06)
	 */
	var $taxPercents = array('GST'=>0.06, 'PST'=> 0.065);
	
	/**
	 * The next id of an item to be added to the cart.
	 * This serves as the key for the $items array.
	 * @var int
	 */
	var $nextID = 0;
	
	/**
	 * A delegate object that allows us to customize some of the 
	 * functionality.  A delegate object can implement the following
	 * methods:
	 *
	 * function checkout(ShoppingCart $cart);
	 * function verifyPayment(ShoppingCart $cart, $details);
	 * @var ShoppingCartDelegate
	 */
	var $delegate = null;
	
	/**
	 * A renderer to display the shopping cart.
	 * @var ShoppingCartRenderer
	 */
	var $renderer = null;
	
	/**
	 * The invoice id that this shopping cart is associated with.
	 * @var int
	 */
	var $invoiceID = null;
	
	/**
	 * The amount added to the order for shipping.
	 * @var ShoppingCartItem
	 */
	public $shipping = null;
	
	/**
	 * The shipping method that is set to be used for this cart.
	 * @var mixed
	 */
	public $shippingMethod = null;
	
	
	
	public $shippingEnabled = true;
	
	public function setRenderer( ShoppingCartRenderer $renderer ){
		$this->renderer = $renderer;
	}
	
	/**
	 * @returns ShoppingCartRenderer
	 */
	public function getRenderer(){
		if ( !isset($this->renderer) ) $this->setRenderer( new ShoppingCartRenderer() );
		return $this->renderer;
	}
	
	/**
	 * The subtotal before discount and taxes are considered.
	 * @returns float
	 */
	function subtotal(){
		$total = 0.0;
		foreach ( $this->items as $item ){
			$total += $item->subtotal();
		}
		if ( $this->shipping ) $total += $this->shipping->subtotal();
		return round($total,2);
	}
	
	
	
	
	/**
	 * The total amount that is taxable for each tax.  This will return an associative
	 * array where the keys are the names of the applicable taxes, and the values
	 * are the amount that is taxable.  Note that this amount is not the amount of tax
	 * that is to be charged.  It is the amount that CAN be taxed.
	 *
	 * @returns array(string => float)
	 */
	function taxables(){
		$taxes = array();
		$items = $this->items;
		if ( $this->shipping ) $items[] = $this->shipping;
		foreach ( $items as $item ){
			foreach ( $this->taxPercents as $taxName=>$taxPercent ){
				if ( !isset($taxes[$taxName]) ) $taxes[$taxName] = 0.0;
				if ( !@$item->taxes[$taxName] ) continue;
				$taxes[$taxName] += $item->subtotal();
			}
		}
		
		return $taxes;
	}
	
	/**
	 * The total amount of tax to be charged.  This will return an associative array
	 * where the keys are the names of the applicable taxes and the values are the
	 * amount of that tax that is charged. This result is rounded to 2 decimal places.
	 * @returns array(string => float)
	 */
	function taxes(){
		$taxes = array();
		$taxables = $this->taxables();
		foreach ( $this->taxPercents as $taxName=>$taxPercent ){
			if ( !isset($taxables[$taxName]) ) $taxables[$taxName] = 0.0;
			$taxes[$taxName] = round(floatval($taxPercent) * floatval($taxables[$taxName]), 2);
		}
		return $taxes;
	}
	
	/**
	 * The total amount due.  subtotal - discount + tax.
	 * @returns float
	 */
	function total(){
		$total = $this->subtotal();
		foreach ($this->taxes() as $tax){
			$total += $tax;
		}
		return $total;
		
	}
	
	/**
	 * Adds an item to the cart.
	 * @param ShoppingCartItem $item
	 */
	function addItem($item){
		$this->items[$this->nextID] = $item;
		return $this->nextID++;
	}
	
	/**
	 * Removes an item from the card given its id.
	 * @param int $id
	 * @returns void
	 */
	function removeItem($id){
		unset($this->items[$id]);
	}
	
	/**
	 * Returns the item with the given id.
	 * @param int $id The id of the item to retrieve.
	 * @returns ShoppingCartItem
	 */
	function getItem($id){
		return $this->items[$id];
	}
	
	function getItemByProductID($id){
		foreach ($this->items as $item){
			if ( $item->productID == $id ) return $item;
		}
		return null;
	}
	
	function getItemsByCategory($category){
		$out = array();
		foreach ($this->items as $item){
			if ( $item->category == $category ) $out[] = $item;
			unset($item);
		}
		return $items;
	}
	
	
	/**
	 * Saves the shopping cart to the session.
	 */
	function save(){
		$_SESSION[ShoppingCart_key][$this->id] = $this;
	}
	
	/**
	 * Loads a shopping cart from the session.
	 * @returns ShoppingCart
	 */
	function load($id=0){
		if ( isset($_SESSION[ShoppingCart_key][$id]) ){
			return $_SESSION[ShoppingCart_key][$id];
		} else {
			return new ShoppingCart;
		}
	}
	
	/**
	 * Empties the shopping cart.  This effectively removes the cart
	 * from the session.
	 * @example
	 * <code>
	 * $cart = ShoppingCart::load();
	 * $cart->empty();  // the cart has been destroyed.
	 *
	 * $cart->save(); // we changed our mind.  The cart is undestroyed.
	 * </code>
	 */
	function emptyCart(){
		unset( $_SESSION[ShoppingCart_key][$this->id]);
	}
	
	
	function checkout(){
		if ( isset( $this->delegate) and method_exists($this->delegate, 'checkout') ){
			return $this->delegate->checkout($this);
		}
	}
	
	function verifyPayment($details){
		if ( isset( $this->delegate) and method_exists($this->delegate, 'verifyPayment') ){
			return $this->delegate->verifyPayment($this, $details);
		}
	}
	
	
	function getPaymentButton(){
		if ( isset( $this->delegate) and method_exists($this->delegate, 'getPaymentButton') ){
			return $this->delegate->getPaymentButton($this, $details);
		}
	
	}
	
	function displayCheckout($params=array()){
		return $this->getRenderer()->renderCheckout($this, $params);
	}
	
	function displayPortlet($params=array()){
		return $this->getRenderer()->renderPortlet($this, $params);
	}
	

}

/**
 * Represents an item in the shopping cart.
 */
class ShoppingCartItem {
	var $productID=null;
	var $quantity=1;
	var $description=null;
	var $unitPrice=null;
	var $taxes = array();
	var $category=null;
	var $shipping=0.0;

	
	function subtotal(){
		return round( (floatval($this->quantity)*floatval($this->unitPrice)) + floatval($this->shipping), 2);
	}
	
	
	
	function isTaxable($taxname){
		return @$this->taxes[$taxname];
	}
	
	function tax($taxPercents = array()){
		$amount = 0.0;
		foreach ( $taxPercents as $name=>$percent ){
			if ( @$this->taxes[$name] ) $amount += round( floatval($percent) * $this->subtotal(), 2 );
		}
		return $amount;
	}
}

class ShoppingCartFactory {
	private static $factory;
	
	public static function setFactory(ShoppingCartFactory $factory){
		self::$factory = $factory;
	}
	
	public static function getFactory(){
		if ( !isset(self::$factory) ) self::$factory = new ShoppingCartFactory();
		return self::$factory;
	}
	
	public function loadCart($id=0){
		return ShoppingCart::load($id);
	}
	
	public function newItem(){
		return new ShoppingCartItem;
	}

}

class ShoppingCartRenderer {
	function renderCheckout( ShoppingCart $cart, $params=array() ){
		//print_r($cart);exit;
		$out = '<table id="'.htmlspecialchars(ShoppingCart_key).'-checkout" width="100%">
				<thead>
					<tr><th style="display:none">Product ID</th><th class="ShoppingCart-quantity">Quantity</th><th class="ShoppingCart-name">Name</th><th class="ShoppingCart-price">Unit Price</th>'.($this->shippingEnabled?'<th class="ShoppingCart-shipping">Shipping</th>':'').'';
					foreach ( $cart->taxPercents as $taxName => $taxPercent ){
						$out .= '<th class="ShoppingCart-tax">'.$taxName.'</th>';
					}
					$out .= '<th class="ShoppingCart-total">Total</th>
					</tr>
				<thead>
				<tbody>
				';
		$even = false;
		$items = $cart->items;
		if ( $cart->shipping ) $items[] = $cart->shipping;
		foreach ( $items as $item ){
			if ( $even ){
				$class = ShoppingCart_key.'-even-row';
			} else {
				$class = ShoppingCart_key.'-odd-row';
			}
			$even = !$even;
			$out .= '<tr class="'.htmlspecialchars($class).'" id="'.htmlspecialchars(ShoppingCart_key).'-'.htmlspecialchars($item->productID).'-lineitem"><td style="display:none">'.$item->productID.'</td>
					<td align="right">'.$item->quantity.'</td>
					<td>'.$item->description.'</td>
					<td align="right">'.money_format('%i',$item->unitPrice).'</td>
					'.($this->shippingEnabled?'<td align="right">'.money_format('%i', $item->shipping).'</td>':'').'
					';
											
			foreach ( $cart->taxPercents as $taxName => $taxPercent ){
				$out .= '<td align="center">'.( $item->isTaxable($taxName) ? 'Y' : 'N').'</td>';
			}
	
			$out .= '<td align="right">'.money_format('%i',$item->subtotal()).'</td></tr>';
			//unset($item);
		}
		
		
		
		$out .= '</tbody></table>';
		
		$out .= '<table id="ShoppingCart-totals">
			<tr><th align="left">Subtotal</th><td>'.number_format($cart->subtotal(),2).'</td></tr>';
		foreach ( $cart->taxes() as $taxName => $amount ){
			$out .= '<tr><th align="left">'.$taxName.'</th><td align="right">'.number_format($amount,2).'</td></tr>';
		}
		$out .= '<tr><th align="left">Total</th><td align="right">'.number_format($cart->total(),2).'</td></tr>
		</table>';
		return $out;
	}
	
	function renderPortlet( ShoppingCart $cart ){
	
	}

}

class ShoppingCartService {
	
	var $message;


	
		
	function addToCart(){
		if ( isset($_POST['--cart_id']) ) $cart_id = $_POST['--cart_id'];
		else $cart_id =0;
		$cart = ShoppingCartFactory::getFactory()->loadCart($cart_id);
		
		$item = ShoppingCartFactory::getFactory()->newItem();
		$item->productID = @$_POST['productID'];
		if ( isset($_POST['quantity']) ) $item->quantity = intval($_POST['quantity']);
		if ( isset($_POST['description']) ) $item->description = $_POST['description'];
		if ( isset($_POST['unitPrice']) ) $item->unitPrice = floatval($_POST['unitPrice']);
		
		if ( isset($_POST['taxes']) and is_array($_POST['taxes']) ){
			foreach ( $_POST['taxes'] as $taxName => $tf ){
				$item->taxes[$taxName] = $tf;
			}
		}
		
		$id = $cart->addItem($item);
		$cart->save();
		
		$this->message = "The item was successfully added to the cart.";
		return $id;
	}
	
	function removeFromCart(){
		if ( isset($_POST['--cart_id']) ) $cart_id = $_POST['--cart_id'];
		else $cart_id =0;
		$cart = ShoppingCartFactory::getFactory()->loadCart($cart_id);
		
		if ( isset($_POST['id']) ){
			$cart->removeItem($_POST['id']);	
		}
		$cart->save();
		$this->message = "The Item was successfully removed from the cart.";
		return;
	}
	
	
	function emptyCart(){
		if ( isset($_POST['--cart_id']) ) $cart_id = $_POST['--cart_id'];
		else $cart_id =0;
		$cart = ShoppingCartFactory::getFactory()->loadCart($cart_id);
		
		$cart->emptyCart();
		$this->message = "The cart was successfully emptied.";
		return;
	}
	
	
	
	
	function handleRequest(){
		$handled = false;
		switch ( @$_POST['--cart-action'] ){
			case 'add': $this->addToCart(); $handled = true; break;
			case 'remove': $this->removeFromCart(); $handled = true; break;
			case 'empty': $this->emptyCart(); $handled = true; break;
		}
		if ( $handled ){
			if ( isset( $_POST['--redirect'] ) ) $redirect = $_POST['--redirect'];
			else if ( @$_SERVER['HTTP_REFERER'] ) $redirect = $_SERVER['HTTP_REFERER'];
			else $redirect = 'about:blank';
			
			if ( strpos($redirect, '?') === false ){
				$redirect .= '?';
			}
			
			$redirect .= '&--msg='.urlencode($this->message);
			header('Location: '.$redirect);
			exit;
		}
	
	}
	
	
}