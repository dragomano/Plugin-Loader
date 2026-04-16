<?php

declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/Support/Bootstrap.php';
require_once __DIR__ . '/Support/SmfNamespaceStubs.php';

use Bugo\PluginLoader\Integration;
use ReflectionMethod;
use Testo\Assert;
use Testo\Test;
use Tests\Support\SmfTestState;
use Tests\Support\TestEnvironment;

final class IntegrationTest
{
	#[Test]
	public function updateSettingsFileRegistersPluginsDefinition(): void
	{
		TestEnvironment::reset();

		$integration = new Integration();
		$settings    = [];

		$integration->updateSettingsFile($settings);

		Assert::same($settings['plugins']['default'], '');
		Assert::same($settings['plugins']['type'], 'string');
		Assert::true(str_contains($settings['plugins']['text'], 'Enabled plugins'));
	}

	#[Test]
	public function adminAreasRegistersPluginManagerArea(): void
	{
		TestEnvironment::reset();

		$integration = new Integration();
		$adminAreas  = ['forum' => ['areas' => []]];

		$integration->adminAreas($adminAreas);

		Assert::same(SmfTestState::last('loadLanguage'), 'PluginLoader/');
		Assert::same($adminAreas['forum']['areas']['plugins']['label'], 'Plugin Manager');
		Assert::same($adminAreas['forum']['areas']['plugins']['subsections'], [
			'browse' => ['Browse plugins'],
			'upload' => ['Add plugins'],
		]);
	}

	#[Test]
	public function preparePluginListUsesEnglishFallbackAndStoredSettings(): void
	{
		TestEnvironment::reset([
			'user' => ['language' => 'russian'],
		]);

		TestEnvironment::createPlugin('demo', [
			'plugin-info.xml' => <<<'XML'
<?xml version="1.0"?>
<plugin id="Author:Demo">
	<name>Demo Plugin</name>
	<description>
		<english>Demo description</english>
	</description>
	<version>1.0.0</version>
	<author>Author</author>
	<license>BSD-3-Clause</license>
	<settings>
		<setting name="title" type="text" default="Default title" />
		<setting name="count" type="int" default="5" />
	</settings>
</plugin>
XML,
			'languages/english.php' => <<<'PHP'
<?php

return [
	'title' => 'Plugin title',
	'count' => 'Plugin count',
];
PHP,
			'settings.ini' => <<<'INI'
title = "Saved title"
INI,
		]);

		$integration = new Integration();

		$this->invokePrivate($integration, 'preparePluginList');

		Assert::same($GLOBALS['context']['pl_enabled_plugins'], []);
		Assert::same($GLOBALS['context']['pl_plugins']['demo']['name'], 'Demo Plugin');

		Assert::same($GLOBALS['context']['pl_plugins']['demo']['settings']['title'], [
			'name'  => 'Plugin title',
			'type'  => 'text',
			'value' => 'Saved title',
		]);

		Assert::same($GLOBALS['context']['pl_plugins']['demo']['settings']['count'], [
			'name'  => 'Plugin count',
			'type'  => 'int',
			'value' => '5',
		]);
	}

	#[Test]
	public function saveSettingsWritesIniWithExpectedFormatting(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('demo', []);

		$integration = new Integration();

		$this->invokePrivate($integration, 'saveSettings', 'demo', [
			'enabled' => true,
			'limit'   => 3,
			'title'   => 'Demo plugin',
		]);

		$contents = file_get_contents(PLUGINS_DIR . '/demo/settings.ini');

		Assert::same($contents, "enabled = 1\nlimit = 3\ntitle = \"Demo plugin\"\n");
	}

	private function invokePrivate(object $object, string $method, mixed ...$arguments): mixed
	{
		$reflection = new ReflectionMethod($object, $method);
		$reflection->setAccessible(true);

		return $reflection->invoke($object, ...$arguments);
	}
}
