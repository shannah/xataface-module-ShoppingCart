<?php
class actions_set_shipping_method {
	function handle(&$params){
	
		if ( $_POST ){
			$app =& Dataface_Application::getInstance();
			$action =& $params['action'];
			
			
			$scTool = Dataface_ModuleTool::getInstance()->loadModule('modules_ShoppingCart');
			
			
			if ( !$_POST['--shipping_method'] ){
				return PEAR::raiseError('Please provide the --shipping_method variable');
			}
			
			
			
			$scTool->setShippingMethod($_POST['--shipping_method']);
			
			$url = $app->url('-action=calculate_shipping');
			
			
			if ( strpos($url, '?') === false ){
				$url .= '?';
			}
			
			$url .= '&--msg='.urlencode('The shipping method has been successfully updated');
			header('Location: '.$url);
			exit;
		} else {
			df_register_skin('shopping_cart', dirname(__FILE__).'/../templates');
			df_display(array(), 'ShoppingCart/set_shipping_method.html');
		}
	}
}