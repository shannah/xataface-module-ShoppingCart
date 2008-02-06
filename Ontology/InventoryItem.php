<?php
import('Dataface/Ontology.php');
class modules_ShoppingCart_Ontology_InventoryItem extends Dataface_Ontology{

	function buildAttributes(){
		$this->fieldnames = array();
		$this->attributes = array();
		
		$description = null;
		$unitPrice = null;
		$taxes = null;
		
		if ( $this->table->getDelegate() and method_exists($this->table->getDelegate(), 'taxes') ){
			$taxes = 'taxes';
		} else {
			$taxes = null;
		}
		
		
		foreach ( $this->table->fields(false,true) as $field ){
			if ( isset($field['ShoppingCart.description']) ) $description = $field['name'];
			if ( isset($field['ShoppingCart.unitPrice']) ) $unitPrice = $field['name'];
			
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
					echo "Checking field $field[name]";
					if ( $this->table->isFloat($field['name']) ){
						$unitPrice = $field['name'];
						break;
					}
				}
			}
		}
		
		
		
		$atts = array('unitPrice'=>$unitPrice, 'description'=>$description, 'taxes'=>$taxes);
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