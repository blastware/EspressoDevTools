<?php
/*
Plugin Name: Developer Tools for Event Espresso
Plugin URI: https://github.com/Blastware/EspressoDevTools
Description: A set of WordPress Admin Bar extensions that aid in the troubleshooting and development of Event Espresso based sites and applications.
Version: 1.0.0
Author: Blastware
Author URI: https://github.com/Blastware
License: GPLv2
*/
require_once __DIR__ . '/vendor/autoload.php';
define('EE_DEV_TOOLS_BASE_PATH', plugin_dir_path(__FILE__));
define('EE_DEV_TOOLS_BASE_URL', plugin_dir_url(__FILE__));
new Blastware\EspressoDevTools\Domain\Services\EspressoDevTools();
