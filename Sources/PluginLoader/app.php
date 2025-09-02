<?php

/**
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2025 Bugo
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
	$path = __DIR__ . DIRECTORY_SEPARATOR . $classname . '.php';

	if (! file_exists($path))
		return false;

	require_once $path;
}

spl_autoload_register('pl_autoloader');

$init = new Bugo\PluginLoader\Integration();
$init();

$enabledPlugins = empty($plugins) ? [] : explode(',', $plugins);

if ($enabledPlugins === [] || SMF === 'BACKGROUND')
	return;

foreach ($enabledPlugins as $plugin) {
	$file = PLUGINS_DIR . '/' . $plugin . '/sources/plugin.php';

	if (is_file($file)) {
		$pluginInstance = require_once $file;

		if ($pluginInstance instanceof Bugo\PluginLoader\Plugin) {
			$pluginInstance();
		}
	}
}
