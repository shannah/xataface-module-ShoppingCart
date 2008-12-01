<?php
/**
 * This action allows the user to calculate the shipping charge for his order.
 *
 * @created May 2008
 * @author Steve Hannah <steve@weblite.ca>
 */
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
		
		$shippingMethod = $scTool->getShippingMethod();

		if ( !$shippingMethod ){
			// The shipping method hasn't been set yet, so we need to redirect to the form
			// for the user to set his shipping method.
			header('Location:'.$app->url('-action=set_shipping_method'));
			exit;
		}

		$handler = $scTool->getShippingHandler();
		if ( method_exists($handler, 'getRequiredFields') ){
			$requiredFields = $handler->getRequiredFields( $shippingMethod->val('shipping_method_name'));
		}
		else {
		
			$requiredFields = array(
				'province', 'postalCode', 'country'
			);
		}
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
			
			$locationPaths = array(
				DATAFACE_PATH.'/modules/ShoppingCart/shipping/handlers/'.basename($shippingMethod->val('shipping_method_name')).'_locations.xml',
				DATAFACE_PATH.'/modules/ShoppingCart/shipping/locations.xml');
			foreach ($locationPaths as $path){
				if (file_exists($path) ){
					$locations = simplexml_load_file($path);
					break;
				}
			}
			if ( !$locations ){
				return PEAR::raiseError("Could not find any valid locations");
			}
			
			//echo $locations->country[0]['name'];
			
			$countries = array();
			foreach ($locations->country as $country ){
				$countries[strval($country['code'])] = strval($countryNames->getName($country['code']));
			}


			
			import('HTML/QuickForm.php');
			$form =& new HTML_QuickForm('shipping_details', 'POST');
			if ( in_array('country', $requiredFields) ){
				$form->addElement('select', 'country', 'Country',$countries, array('onchange'=>'updateProvinces(this);', 'id'=>'country-select'));
				$form->setDefaults(array('country'=>$invoice->val('country')));
			}
			
			if ( in_array('province', $requiredFields) ){
				$form->addElement('select', 'province', 'Province',array(),array('id'=>'province-select'));
				$form->setDefaults(array('province'=>$invoice->val('province')));
			}
			
			if ( in_array('postalCode', $requiredFields) ){
				$form->addElement('text', 'postalCode', 'Postal Code');
				$form->setDefaults(array('postalCode'=>$invoice->val('postalCode')));
			}
			

			$form->addElement('submit', 'calculate', 'Calculate Shipping Now');
			$form->addElement('hidden', '-action', 'calculate_shipping');
			$form->addElement('hidden', '--change-info', @$_REQUEST['--change-info']);
			
			
			
			foreach ( $requiredFields as $field ){
				
				$form->addRule($field, 'Missing required field '.$field, 'required',null, 'client');
			}
			
			if ( $form->validate() ){
				$vals = $form->exportValues();
				$invoice->setValues(array(
					'province' => $vals['province'],
					'postalCode' => $vals['postalCode'],
					'country' => $vals['country']
				));
				

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
		$res = $scTool->calculateShipping();
		
		header('Location: '.$app->url('-action=view_cart').'&--msg='.urlencode('Shipping has been calculated.'));
		exit;
		print_r($cart);
		echo "here";exit;
		
		
		
		
	}
}