<?php

/**
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2025 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 */

namespace Bugo\PluginLoader;

use Bugo\PluginLoader\Attributes\Hook;
use ReflectionClass;

trait HasInvoke
{
	public function __invoke(): void
	{
		$reflectionClass = new ReflectionClass(static::class);

		foreach ($reflectionClass->getMethods() as $method) {
			$attributes = $method->getAttributes(Hook::class);

			foreach ($attributes as $attribute) {
				/** @var Hook $hook */
				$hook = $attribute->newInstance();

				$callback = $method->class . '::' . $method->name . '#';

				add_integration_function($hook->name, $callback, false, $method->getFileName());
			}
		}
	}
}
