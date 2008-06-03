<?php
class actions_get_shipping_provinces {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$locations = simplexml_load_file(DATAFACE_PATH.'/modules/ShoppingCart/shipping/locations.xml');
		
		$provinces = array();
		
		
		foreach ($locations->country as $country){
			if ( $country['code'] == $_REQUEST['country'] ){
				foreach ($country->province as $province){
					$provinces[] = array('code' => strval($province['code']), 'name'=>strval($province['name']));
				}
				break;
			}
		}
		
		import('Services/JSON.php');
		$json =& new Services_JSON;
		
		$provinces_json = $json->encode($provinces);
		
		header('Content-type: text/json; charset='.$app->_conf['oe']);
		echo $provinces_json;
		exit;
		
		
	}
}