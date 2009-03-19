<?php
class actions_payment_cancelled {
	function handle(&$record){
		header("Location: ".DATAFACE_SITE_HREF.'?-action=view_cart&--msg='.urlencode("Payment was cancelled."));
		exit;
	}
}