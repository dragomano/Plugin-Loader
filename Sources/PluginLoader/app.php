<?php

/**
 * app.php
 *
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 *
 * @version 0.5
 */

if (!defined('SMF'))
	die('No direct access...');

global $boarddir, $boardurl, $plugins;

defined('PLUGINS_DIR') || define('PLUGINS_DIR', $boarddir . '/Plugins');
defined('PLUGINS_URL') || define('PLUGINS_URL', $boardurl . '/Plugins');

require_once __DIR__ . '/Integration.php';
require_once __DIR__ . '/Plugin.php';

$loader = new Bugo\PluginLoader\Integration();
$loader->hooks();

$enabled_plugins = empty($plugins) ? [] : explode(',', $plugins);

if (empty($enabled_plugins) || SMF === 'BACKGROUND')
	return;

foreach ($enabled_plugins as $plugin)
{
	$file = PLUGINS_DIR . '/' . $plugin . '/sources/plugin.php';
	if (is_file($file))
	{
		$plugin_instance = require_once $file;
		if ($plugin_instance instanceof Bugo\PluginLoader\Plugin)
			$plugin_instance->hooks();
	}
}
