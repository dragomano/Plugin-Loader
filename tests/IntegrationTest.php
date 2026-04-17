<?php

declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/Support/Bootstrap.php';
require_once __DIR__ . '/Support/SmfNamespaceStubs.php';

use Bugo\PluginLoader\Integration;
use ReflectionMethod;
use RuntimeException;
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
	public function preparePluginListSkipsPluginWithInvalidXml(): void
	{
		TestEnvironment::reset();

		TestEnvironment::createPlugin('broken', [
			'plugin-info.xml' => <<<'XML'
<?xml version="1.0"?>
<plugin id="Author:Broken">
	<name>Broken Plugin</name>
	<description>Missing closing tags
XML,
		]);

		TestEnvironment::createPlugin('working', [
			'plugin-info.xml' => <<<'XML'
<?xml version="1.0"?>
<plugin id="Author:Working">
	<name>Working Plugin</name>
	<description>
		<english>Working description</english>
	</description>
	<version>1.0.0</version>
	<author>Author</author>
	<license>BSD-3-Clause</license>
</plugin>
XML,
		]);

		$integration = new Integration();

		$this->invokePrivate($integration, 'preparePluginList');

		Assert::false(isset($GLOBALS['context']['pl_plugins']['broken']));
		Assert::same($GLOBALS['context']['pl_plugins']['working']['name'], 'Working Plugin');
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

	#[Test]
	public function handleToggleValidatesSessionBeforeReadingPayload(): void
	{
		TestEnvironment::reset();

		$_REQUEST = ['toggle' => 1];

		$integration = new Integration();

		try {
			$this->invokePrivate($integration, 'handleToggle');

			Assert::fail('Expected redirect exception was not thrown.');
		} catch (RuntimeException $exception) {
			Assert::same($exception->getMessage(), 'redirect:action=admin;area=plugins');
		}

		Assert::same(SmfTestState::last('checkSession'), [
			'type'    => 'request',
			'from'    => 'admin',
			'isFatal' => true,
		]);
	}

	#[Test]
	public function handleRemoveValidatesSessionBeforeReadingPayload(): void
	{
		TestEnvironment::reset();

		$_REQUEST = ['remove' => 1];

		$integration = new Integration();

		try {
			$this->invokePrivate($integration, 'handleRemove');

			Assert::fail('Expected redirect exception was not thrown.');
		} catch (RuntimeException $exception) {
			Assert::same($exception->getMessage(), 'redirect:action=admin;area=plugins');
		}

		Assert::same(SmfTestState::last('checkSession'), [
			'type'    => 'request',
			'from'    => 'admin',
			'isFatal' => true,
		]);
	}

	#[Test]
	public function handleRemoveForbidsDeletingEnabledPlugin(): void
	{
		TestEnvironment::reset([], ['plugins' => 'demo']);

		$_REQUEST = ['remove' => 1];

		$GLOBALS['smf_json_decode_override'] = ['plugin' => 'demo'];

		$integration = new Integration();

		try {
			$this->invokePrivate($integration, 'handleRemove');

			Assert::fail('Expected redirect exception was not thrown.');
		} catch (RuntimeException $exception) {
			Assert::same($exception->getMessage(), 'redirect:action=admin;area=plugins');
		}

		Assert::same(SmfTestState::all('deltree'), []);
	}

	#[Test]
	public function templateMainPassesSessionDataToJavascriptActions(): void
	{
		TestEnvironment::reset();
		$GLOBALS['context']['pl_plugins'] = [
			'demo' => [
				'name'    => 'Demo Plugin',
				'website' => '',
				'version' => '1.0.0',
				'license' => [
					'value'       => 'BSD-3-Clause',
					'@attributes' => ['url' => ''],
				],
				'author' => [
					'value'       => 'Author',
					'@attributes' => ['url' => ''],
				],
				'description' => [
					'english' => 'Demo description',
				],
				'settings' => [],
			],
		];

		$GLOBALS['context']['pl_enabled_plugins']  = [];
		$GLOBALS['settings']['default_images_url'] = 'https://example.test/images';

		$GLOBALS['txt']['pl_info'] = 'Info %s';
		$GLOBALS['txt']['remove']  = 'Remove';
		$GLOBALS['txt']['author']  = 'Author';

		require_once dirname(__DIR__) . '/src/Themes/default/PluginLoader.template.php';

		ob_start();
		template_main();
		$output = ob_get_clean();

		Assert::true(str_contains(
			$output,
			'const plugin = new PluginLoader("session_var", "session-id");'
		));
	}

	private function invokePrivate(object $object, string $method, mixed ...$arguments): mixed
	{
		$reflection = new ReflectionMethod($object, $method);
		$reflection->setAccessible(true);

		return $reflection->invoke($object, ...$arguments);
	}
}
