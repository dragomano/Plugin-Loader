<?php

/**
 * HasInvoke.php
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

use ReflectionClass;

trait HasInvoke
{
	public function __invoke(): void
	{
		$reflectionClass = new ReflectionClass(static::class);
		foreach ($reflectionClass->getMethods() as $method) {
			$attributes = $method->getAttributes(Hook::class);

			foreach ($attributes as $attribute) {
				$attribute->newInstance();
			}
		}
	}
}
