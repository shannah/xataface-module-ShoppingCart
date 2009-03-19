<?php
require_once 'upsRate.php';

$ups = new upsRate;
$ups->setCredentials('9C377C6E7749890C','weblite','ups*com', '000094A0W0');
print_r($ups->getRate('CA','CA','V3J7E3','V2Z1A7','11',10,10,10,10));
echo "\n------\n";
print_r($ups->getRate('CA','CA','V3J7E3','V2Z1A7','11',array(10,10),array(10,10),array(10,10),array(10,10),array(10,10)));