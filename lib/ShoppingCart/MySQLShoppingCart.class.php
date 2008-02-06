<?php
require_once dirname(__FILE__).'/ShoppingCart.class.php';
define('MySQLShoppingCart_table', 'ShoppingCart');
class MySQLShoppingCart extends ShoppingCart {

	var $db;
	
	function MySQLShoppingCart($db){
		$this->db = $db;
	}
	
	function load($db, $id=null){
		if ( !isset($id) ){
			
			if ( isset($_SESSION[ShoppingCart_key]['id']) ) $id = $_SESSION[ShoppingCart_key]['id'];
			
		}
		
		if ( !isset($id) ){
			// At this point it looks like we'll have to create a new cart.
			return new MySQLShoppingCart($db);
		} else {
			$res = mysql_query("select * from `".MySQLShoppingCart_table."` where id='".addslashes($id)."' limit 1", $db);
			if ( !$res ) trigger_error(mysql_error($db), E_USER_ERROR);
			
			if ( mysql_num_rows($res) == 0 ){
				// The cart is not in the table.
				// so we will create a new cart.
				@mysql_free_result($res);
				return new MySQLShoppingCart($db);
				
				// We don't add it to the database yet.  That happens when we save.
			}
			
			$row = mysql_fetch_assoc($res);
			$cart = unserialize($row['object']);
			$cart->db = $db;
			@mysql_free_result($res);
			return $cart;
		}
	}
	
	function save(){
	
		if ( !$this->id ){
			$res = mysql_query("insert into `".MySQLShoppingCart_table."` (`object`) values (NULL)", $this->db);
			if ( !$res ) trigger_error(mysql_error($this->db), E_USER_ERROR);
			
			$this->id = mysql_insert_id($this->db);
		
		}

	
		// find out if it already exists in the table.
		$res = mysql_query("select id from `".MySQLShoppingCart_table."` where id='".addslashes($this->id)."' limit 1", $this->db);
		if ( !$res ) trigger_error(mysql_error($db), E_USER_ERROR);
		
		if ( mysql_num_rows($res) == 0 ){
			// It doesn't exist in the database yet.  Let's create a record for it.
			@mysql_free_result($res);
			$res = mysql_query("insert into `".MySQLShoppingCart_table."` (`object`) values (NULL)", $this->db);
			if ( !$res ) trigger_error(mysql_error($this->db), E_USER_ERROR);
			
			$this->id = mysql_insert_id($this->db);
			
		
		}
		
		// we update
		@mysql_free_result($res);
		$res = mysql_query("update `".MySQLShoppingCart_table."` set `object` = '".addslashes(serialize($this))."' where `id`='".addslashes($this->id)."' limit 1", $this->db);
		if ( !$res ) trigger_error(mysql_error($res), $this->db);
		
		if ( !isset($_SESSION[ShoppingCart_key]['id'] or $_SESSION[ShoppingCart_key]['id'] != $this->id ){
			$_SESSION[ShoppingCart_key]['id'] = $this->id;
		}
		
	}
	
	
}