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
 * An action to handle paypals instant payment notifications.
 * 
 * @author Steve Hannah <steve@weblite.ca>
 * @created December 2007
 */
class actions_paypal_ipn {
	function handle(&$params){
		require_once DATAFACE_PATH.'/modules/ShoppingCart/lib/paypal.class.php';
		
		$p = new paypal_class;
		if ( $p->validate_ipn() ){
			ob_start();
			print_r($p->ipn_data);
			$d = ob_get_contents();
			ob_end_clean();
			mail('steve@weblite.ca', 'Paypal data', $d);
			if ( !isset($p->ipn_data['invoice']) ){
				error_log('Failed to validate invoice for payment because paypal did not specify an invoice in its ipn data');
				exit;
			}
			
			list($appid,$invoiceID) = explode('.',$p->ipn_data['invoice']);
			$invoice = df_get_record('dataface__invoices', array('invoiceID'=>$invoiceID));
			if ( !$invoice ){
				error_log('Failed to validate invoice for payment of invoice id '.$invoiceID.' because the invoice does not exist.');
				exit;
			}
			
			$invoice->setValues(array(
				'firstName' => $p->ipn_data['first_name'],
				'lastName' => $p->ipn_data['last_name'],
				'address1' => $p->ipn_data['address_street'],
				
				'city' => $p->ipn_data['address_city'],
				'province' => $p->ipn_data['address_state'],
				'country' => $p->ipn_data['address_country'],
				
				'postalCode' => $p->ipn_data['address_zip'],
				'phone' => $p->ipn_data['contact_phone'],
				'email' => $p->ipn_data['payer_email']
			));
				
			
			if ( $p->ipn_data['payment_status'] == 'Completed' ){
				$invoice->setValue('status', 'PAID');
			} else {
				$invoice->setValue('status', 'PROCESSING');
				$invoice->setValue('status_note', @$p->ipn_data['pending_reason']);
			}
			$invoice->setValue('dateModified', date('Y-m-d H:i:s'));
			$invoice->save();
			file_put_contents('/tmp/ipn_test', 'We made it');
			
		}
	}

}