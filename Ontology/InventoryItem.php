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
import('Dataface/Ontology.php');

/**
 * An ontology to encapsulate any item that can be added to a shopping cart.
 * Currently this supports the following attributes:
 *	Description: the item description
 *	Unit Price: The price of the item per unit
 *	Taxes : The various taxes on the item.
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created December 2007
 */
class modules_ShoppingCart_Ontology_InventoryItem extends Dataface_Ontology{

	/**
	 * Implements Dataface_Ontology::buildAttributes()
	 */
	function buildAttributes(){
		$this->fieldnames = array();
		$this->attributes = array();
		
		$description = null;
		$unitPrice = null;
		$taxes = null;
		$weight = null;
		$width = null;
		$height = null;
		$length = null;
		
		if ( $this->table->getDelegate() and method_exists($this->table->getDelegate(), 'taxes') ){
			$taxes = 'taxes';
		} else {
			$taxes = null;
		}
		
		
		foreach ( $this->table->fields(false,true) as $field ){
			if ( isset($field['ShoppingCart.description']) ) $description = $field['name'];
			if ( isset($field['ShoppingCart.unitPrice']) ) $unitPrice = $field['name'];
			if ( isset($field['ShoppingCart.weight']) ) $weight = $field['name'];
			if ( isset($field['ShoppingCart.width']) ) $width = $field['name'];
			if ( isset($field['ShoppingCart.height']) ) $height = $field['name'];
			if ( isset($field['ShoppingCart.length']) ) $length = $field['name'];
			
		}
		
		
		
		if ( !isset($unitPrice) ){
			$fields =& $this->table->fields(false,true);
			$candidates = preg_grep('/price/i', array_keys($fields) );
			foreach ( $candidates as $field ){
				if ( $this->table->isFloat($field) ){
					$unitPrice = $field;
					break;
				}
			}
			
			if ( !isset($unitPrice) ){
				foreach ( $fields as $field ){
					//echo "Checking field $field[name]";
					if ( $this->table->isFloat($field['name']) ){
						$unitPrice = $field['name'];
						break;
					}
				}
			}
		}
		
		if ( !isset($weight) ){
			$fields =& $this->table->fields(false,true);
			$candidates = preg_grep('/weight|wt/i', array_keys($fields) );
			foreach ( $candidates as $field ){
				if ( $this->table->isFloat($field) ){
					$weight = $field;
					break;
				}
			}
			
			
		}
		
		if ( !isset($width) ){
			$fields =& $this->table->fields(false,true);
			$candidates = preg_grep('/width/i', array_keys($fields) );
			foreach ( $candidates as $field ){
				if ( $this->table->isFloat($field) ){
					$width = $field;
					break;
				}
			}
			
			
		}
		
		if ( !isset($height) ){
			$fields =& $this->table->fields(false,true);
			$candidates = preg_grep('/height/i', array_keys($fields) );
			foreach ( $candidates as $field ){
				if ( $this->table->isFloat($field) ){
					$height = $field;
					break;
				}
			}
			
			
		}
		
		
		if ( !isset($length) ){
			$fields =& $this->table->fields(false,true);
			$candidates = preg_grep('/length/i', array_keys($fields) );
			foreach ( $candidates as $field ){
				if ( $this->table->isFloat($field) ){
					$weight = $field;
					break;
				}
			}
			
			
		}
		
		
		
		
		$atts = array('unitPrice'=>$unitPrice, 'description'=>$description, 'taxes'=>$taxes, 'weight'=>$weight, 'height'=>$height, 'length'=>$length);
		foreach ($atts as $key=>$val ){
			if ( isset($val) ){
				$field =& $this->table->getField($val);
				$this->attributes[$key] =& $field;
				unset($field);
				$this->fieldnames[$key] = $val;
			}
		}
		
		//print_r($this->fieldnames);exit;
		
		return true;
		
	}
}