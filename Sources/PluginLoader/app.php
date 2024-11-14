<?php

/**
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2024 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 */

if (! defined('SMF'))
	die('No direct access...');

global $boarddir, $boardurl, $plugins;

defined('PLUGINS_DIR') || define('PLUGINS_DIR', $boarddir . '/Plugins');
defined('PLUGINS_URL') || define('PLUGINS_URL', $boardurl . '/Plugins');

/**
 * @param $classname
 * @return false|void
 */
function pl_autoloader($classname)
{
	if (! str_contains($classname, 'Bugo\PluginLoader'))
		return false;

	$classname = str_replace('\\', '/', str_replace('Bugo\PluginLoader\\', '', $classname));
	$file_path = __DIR__ . DIRECTORY_SEPARATOR . $classname . '.php';

	if (! file_exists($file_path))
		return false;

	require_once $file_path;
}

spl_autoload_register('pl_autoloader');

$init = new Bugo\PluginLoader\Integration();
$init();

$enabled_plugins = empty($plugins) ? [] : explode(',', $plugins);

if ($enabled_plugins === [] || SMF === 'BACKGROUND')
	return;

foreach ($enabled_plugins as $plugin) {
	$file = PLUGINS_DIR . '/' . $plugin . '/sources/plugin.php';

	if (is_file($file)) {
		$plugin_instance = require_once $file;

		if ($plugin_instance instanceof Bugo\PluginLoader\Plugin) {
			$plugin_instance();
		}
	}
}
