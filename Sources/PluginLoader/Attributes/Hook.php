<?php

/**
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2025 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 */

namespace Bugo\PluginLoader\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Hook
{
	public function __construct(public string $name) {}
}
