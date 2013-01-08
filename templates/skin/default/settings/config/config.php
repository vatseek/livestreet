<?php

$config = array();

$config['head']['default']['js'] = Config::Get('head.default.js');
$config['head']['default']['js'][] = '___path.static.skin___/js/template.js';

$config['head']['default']['css'] = array(
	// Framework styles
	"___path.root.server___/templates/framework/css/reset.css",
	"___path.root.server___/templates/framework/css/helpers.css",
	"___path.root.server___/templates/framework/css/text.css",
	// Template styles
	"___path.static.skin___/css/main.css",
	"___path.static.skin___/css/modals.css",
);


return $config;
?>