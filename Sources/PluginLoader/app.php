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
 * @version 0.1
 */

if (!defined('SMF'))
	die('No direct access...');

require_once __DIR__ . '/functions.php';

require_once __DIR__ . '/Integration.php';

$loader = new Bugo\PluginLoader\Integration();
$loader->hooks();

global $plugins;

$enabled_plugins = empty($plugins) ? [] : explode(',', $plugins);

if (empty($enabled_plugins))
	return;

foreach ($enabled_plugins as $plugin)
{
	$file = dirname(__DIR__, 2) . '/Plugins/' . $plugin . '/sources/Integration.php';
	if (is_file($file))
	{
		add_integration_function('integrate_pre_load', 'Integration::hooks#', false, $file);
	}
}
