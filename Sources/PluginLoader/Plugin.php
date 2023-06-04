<?php

/**
 * Plugin.php
 *
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 *
 * @version 0.3
 */

namespace Bugo\PluginLoader;

if (!defined('SMF'))
	die('No direct access...');

abstract class Plugin
{
	abstract public function hooks();
}
