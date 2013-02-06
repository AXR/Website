<?php

namespace Hssdoc;

$hssdoc_domain = \Config::get('/shared/hssdoc_url')->host;

$router->route('/^\/$/', array(
	'domain' => $hssdoc_domain,
	'controller' => '\\Hssdoc\\ViewController',
	'run' => 'run',
	'args' => array(ROOT . '/views/hssdoc.html')
));

$router->route('/^\/(@\w+)\/?$/', array(
	'domain' => $hssdoc_domain,
	'controller' => '\\Hssdoc\\ObjectController',
	'run' => 'run',
	'args' => array(1)
));
