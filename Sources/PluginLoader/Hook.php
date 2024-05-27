<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/**
 * Hook.php
 *
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2024 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 *
 * @version 0.6
 */

namespace Bugo\PluginLoader;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Hook
{
	public function __construct(string $name, string $method, string $file)
	{
		add_integration_function($name, $method, false, $file);
	}
}
