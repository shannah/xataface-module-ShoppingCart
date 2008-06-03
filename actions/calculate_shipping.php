<?php
class actions_calculate_shipping {
	function handle(&$params){
	
		$app =& Dataface_Application::getInstance();
		$action =& $params['action'];
		
		import('modules/ShoppingCart/lib/ShoppingCart/ShoppingCart.class.php');
		$cart = ShoppingCartFactory::getFactory()->loadCart();
		
		$scTool = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart');
		
		$invoice = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart')->createInvoice($paymentMethod);
		if ( PEAR::isError($invoice) ) return $invoice;
		
		// Now we need to make sure that we have all of the information that we need
		
		$requiredFields = array(
			'province', 'postalCode', 'country', 'shipping_method'
		);
		$missing = false;
		foreach ($requiredFields as $field){
			if ( !$invoice->val($field) ){
				$missing = true;
			}
		}
		
		import('I18Nv2/Country.php');

		$countryNames = new I18Nv2_Country($app->_conf['lang'], $app->_conf['oe']);
		
		
		
		if ($missing or @$_REQUEST['--change-info']){
			// some required information about the destination is missing
			// We need to collect it.
			
			$locations = simplexml_load_file(DATAFACE_PATH.'/modules/ShoppingCart/shipping/locations.xml');
			//echo $locations->country[0]['name'];
			
			$countries = array();
			foreach ($locations->country as $country ){
				$countries[strval($country['code'])] = strval($countryNames->getName($country['code']));
			}


			$shippingMethods = $scTool->getShippingMethods(array('shipping_method_enabled'=>1));
			$methods = array();
			foreach ($shippingMethods as $sm){
				$methods[$sm->val('shipping_method_name')] = $sm->val('shipping_method_label');
			}

			import('HTML/QuickForm.php');
			$form =& new HTML_QuickForm('shipping_details', 'POST');
			$form->addElement('select', 'country', 'Country',$countries, array('onchange'=>'updateProvinces(this);', 'id'=>'country-select'));
			$form->addElement('select', 'province', 'Province',array(),array('id'=>'province-select'));
			
			$form->addElement('text', 'postalCode', 'Postal Code');
			$form->addElement('select', 'shipping_method', 'Shipping Method', $methods);
			$form->addElement('submit', 'calculate', 'Calculate Shipping Now');
			$form->addElement('hidden', '-action', 'calculate_shipping');
			$form->addElement('hidden', '--change-info', @$_REQUEST['--change-info']);
			
			$form->setDefaults(array(
				'province' => $invoice->val('province'),
				'country' => $invoice->val('country'),
				'postalCode' => $invoice->val('postalCode'),
				'shipping_method' => ($scTool->getShippingMethod() ? $scTool->getShippingMethod()->val('shipping_method_name') : null)
			));
			
			foreach ( $requiredFields as $field ){
				
				$form->addRule($field, 'Missing required field '.$field, 'required',null, 'client');
			}
			
			if ( $form->validate() ){
				$vals = $form->exportValues();
				$invoice->setValues(array(
					'province' => $vals['province'],
					'postalCode' => $vals['postalCode'],
					'country' => $vals['country'],
					'shipping_method' => $vals['shipping_method']
				));
				

				$scTool->setShippingMethod($vals['shipping_method']);

				$cart->shippingMethod = $vals['shipping_method'];
				$cart->save();
				$invoice->save();
				
				//  Now that we should have all of the required destination information
				// we can forward back to ourselves and continue the calculation.
				
				header('Location: '.$app->url(''));
				exit;
			}
			
			ob_start();
			$form->display();
			$form_output = ob_get_contents();
			ob_end_clean();
			
			df_register_skin('cart', 'modules/ShoppingCart/templates');
			
			df_display(array('form'=>$form_output), 'ShoppingCart/missing_shipping_info.html');
			exit;
		}
	
						//print_r($scTool->getShippingMethod()->vals());exit;
		$p = $params['action'];
		
		$p = array_merge($p, $scTool->getShippingMethod()->vals(), $invoice->vals());
		
		
		$res = call_user_func($scTool->getShippingHandler(), $p);
		
		print_r($cart);
		echo "here";exit;
		
		
		
		
	}
}