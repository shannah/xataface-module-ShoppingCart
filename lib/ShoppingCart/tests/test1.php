<?php

require_once dirname(__FILE__).'/../ShoppingCart.class.php';

function displayCart(&$cart){


	$out = '<h2>Cart Items</h2>';
	$out .= $cart->displayCheckout();
	return $out;
}

function displayAddItemForm(&$cart){
	$out = '
	<form action="'.$_SERVER['PHP_SELF'].'" method="post">
	<table>
		<tr><th>Product ID</th><td><input type="text" name="productID" /></td></tr>
		<tr><th>Description</th><td><input type="text" name="description" /></td></tr>
		<tr><th>Price</th><td><input type="text" name="unitPrice" /></td></tr>

		<tr><th>Quantity</th><td><input type="text" name="quantity" /></td></tr>
		';
	foreach ($cart->taxPercents as $taxName=>$amount ){
		$out .= '<tr><th>'.$taxName.'</th><td><input type="checkbox" name="taxes['.$taxName.']" value="1" /></td></tr>';
		
	}
	$out .= '
	</table>
	<input type="hidden" name="--cart-action" value="add" />
	<input type="submit" name="submit" value="Add To Cart" />
	
	</form>
	';
	return $out;
}
session_start();

$service = new ShoppingCartService;
$service->handleRequest();

$cart = ShoppingCartFactory::getFactory()->loadCart();

echo displayCart($cart);
echo displayAddItemForm($cart);
