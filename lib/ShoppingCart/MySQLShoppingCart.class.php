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
 
require_once dirname(__FILE__).'/ShoppingCart.class.php';
define('MySQLShoppingCart_table', 'ShoppingCart');

/**
 * An extension of shopping cart that stores the cart in a MySQL database
 * instead of a session.  This is untested an incomplete.
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created December 2007
 */
class MySQLShoppingCart extends ShoppingCart {

	var $db;
	
	function MySQLShoppingCart($db){
		$this->db = $db;
	}
	
	
	/**
	 * Loads the shopping cart fromt he database.
	 *
	 * @param resource $db The database resource handle.
	 * @param int $id The id of the shopping cart.
	 * @return MySQLShoppingCart The cart.
	 */
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
	
	
	/**
	 * Saves the shopping cart back to the database.
	 */
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