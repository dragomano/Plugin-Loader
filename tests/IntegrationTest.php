<?php

declare(strict_types=1);

namespace Tests;

use Bugo\PluginLoader\Integration;
use ReflectionMethod;
use RuntimeException;
use Testo\Assert;
use Testo\Test;
use Tests\Support\SmfTestState;
use Tests\Support\TestEnvironment;
use ZipArchive;

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
	public function mainSetsTabDataAndRunsDefaultBrowseAction(): void
	{
		TestEnvironment::reset();

		file_put_contents($GLOBALS['sourcedir'] . '/ManageSettings.php', "<?php\n");

		$integration = new Integration();
		$integration->main();

		Assert::same($GLOBALS['context']['admin']['tab_data']['title'], 'Plugin Manager');
		Assert::same($GLOBALS['context']['admin']['tab_data']['tabs']['browse']['description'], 'Browse plugins');
		Assert::same($GLOBALS['context']['admin']['tab_data']['tabs']['upload']['description'], 'Upload plugins');
		Assert::same(SmfTestState::last('loadCSSFile'), ['plugin_loader.css', []]);
		Assert::same(SmfTestState::last('loadJavaScriptFile'), ['plugin_loader.js', ['minimize' => true]]);
		Assert::same(SmfTestState::last('loadTemplate'), 'PluginLoader');
		Assert::same($GLOBALS['context']['sub_template'], 'main');
	}

	#[Test]
	public function mainFallsBackToBrowseWhenSubActionIsUnknown(): void
	{
		TestEnvironment::reset([
			'sub_action' => 'missing',
		]);

		file_put_contents($GLOBALS['sourcedir'] . '/ManageSettings.php', "<?php\n");

		$integration = new Integration();
		$integration->main();

		Assert::same($GLOBALS['context']['sub_action'], 'browse');
		Assert::same($GLOBALS['context']['sub_template'], 'main');
	}

	#[Test]
	public function uploadAreaSetsUploadContextWithoutSubmittedPackage(): void
	{
		TestEnvironment::reset();

		$integration = new Integration();
		$integration->uploadArea();

		Assert::same(SmfTestState::last('loadLanguage'), 'Packages');
		Assert::same(SmfTestState::last('loadCSSFile'), ['plugin_loader.css', []]);
		Assert::same(SmfTestState::last('loadTemplate'), 'PluginLoader');
		Assert::same($GLOBALS['context']['page_title'], 'Plugin Manager - Add plugins');
		Assert::same($GLOBALS['context']['upload_success'], false);
		Assert::same($GLOBALS['context']['sub_template'], 'upload');
		Assert::true($GLOBALS['context']['max_file_size'] > 0);
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
	public function preparePluginListMarksPluginWithEmptyMetadataAsUnavailable(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('empty-meta', [
			'plugin-info.xml' => '',
		]);

		$integration = new Integration();

		$this->invokePrivate($integration, 'preparePluginList');

		Assert::false($GLOBALS['context']['pl_plugins']['empty-meta']);
	}

	#[Test]
	public function preparePluginListSupportsSingleSettingDefinitionAndLocalizedLanguageFile(): void
	{
		TestEnvironment::reset([
			'user' => ['language' => 'russian'],
		]);

		TestEnvironment::createPlugin('single-setting', [
			'plugin-info.xml' => <<<'XML'
<?xml version="1.0"?>
<plugin id="Author:Single">
	<name>Single Plugin</name>
	<description>
		<english>Single description</english>
	</description>
	<version>1.0.0</version>
	<author>Author</author>
	<license>BSD-3-Clause</license>
	<settings>
		<setting name="title" type="text" default="Default title" />
	</settings>
</plugin>
XML,
			'languages/english.php' => <<<'PHP'
<?php

return [
	'title' => 'English title',
];
PHP,
			'languages/russian.php' => <<<'PHP'
<?php

return [
	'title' => 'Russian title',
];
PHP,
		]);

		$integration = new Integration();

		$this->invokePrivate($integration, 'preparePluginList');

		Assert::same($GLOBALS['context']['pl_plugins']['single-setting']['settings']['title'], [
			'name'  => 'Russian title',
			'type'  => 'text',
			'value' => 'Default title',
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

	#[Test]
	public function saveSettingsSkipsWritingEmptyPayload(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('demo', []);

		$integration = new Integration();

		$this->invokePrivate($integration, 'saveSettings', 'demo', []);

		Assert::false(is_file(PLUGINS_DIR . '/demo/settings.ini'));
	}

	#[Test]
	public function handleSaveReturnsEarlyWithoutSaveRequest(): void
	{
		TestEnvironment::reset();

		$integration = new Integration();

		$this->invokePrivate($integration, 'handleSave');

		Assert::same(SmfTestState::all('checkSession'), []);
	}

	#[Test]
	public function handleSavePersistsSupportedSettingTypes(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('demo', []);

		$_REQUEST = [
			'save'        => 1,
			'plugin_name' => 'demo',
			'enabled'     => '1',
			'limit'       => '7',
			'title'       => 'Stored title',
		];

		$GLOBALS['context']['pl_plugins'] = [
			'demo' => [
				'settings' => [
					'enabled' => ['type' => 'check'],
					'limit'   => ['type' => 'int'],
					'title'   => ['type' => 'text'],
					'missing' => ['type' => 'text'],
				],
			],
		];

		$integration = new Integration();

		$this->invokePrivate($integration, 'handleSave');

		Assert::same(SmfTestState::last('checkSession'), [
			'type'    => 'request',
			'from'    => 'admin',
			'isFatal' => true,
		]);
		Assert::same(
			file_get_contents(PLUGINS_DIR . '/demo/settings.ini'),
			"enabled = 1\nlimit = 7\ntitle = \"Stored title\"\n"
		);
	}

	#[Test]
	public function extractPackageRejectsZipWithPathTraversalEntries(): void
	{
		TestEnvironment::reset();

		$zipPath = $this->createZipArchive([
			'demo/plugin-info.xml' => <<<'XML'
<?xml version="1.0"?>
<plugin id="Author:Demo"></plugin>
XML,
			'../escape.txt' => 'escape',
		]);

		$_REQUEST = ['get' => 1];

		$_FILES = [
			'package' => [
				'name'     => 'demo.zip',
				'type'     => 'application/zip',
				'tmp_name' => $zipPath,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$integration = new Integration();

		Assert::false($this->invokePrivate($integration, 'extractPackage'));
		Assert::same($GLOBALS['context']['upload_error'], 'Wrong file');
		Assert::false(is_file(dirname(PLUGINS_DIR) . '/escape.txt'));

		unlink($zipPath);
	}

	#[Test]
	public function extractPackageRejectsAbsoluteAndWindowsStylePaths(): void
	{
		TestEnvironment::reset();

		foreach ([
			'/etc/passwd' => 'unix',
			'C:/Windows/system32' => 'windows',
		] as $entry => $prefix) {
			$zipPath = $this->createZipArchive([
				'demo/plugin-info.xml' => '<?xml version="1.0"?><plugin id="Author:Demo"></plugin>',
				$entry => 'escape',
			]);

			$_REQUEST = ['get' => 1];
			$_FILES = [
				'package' => [
					'name'     => $prefix . '.zip',
					'type'     => 'application/zip',
					'tmp_name' => $zipPath,
					'error'    => UPLOAD_ERR_OK,
				],
			];

			$integration = new Integration();

			Assert::false($this->invokePrivate($integration, 'extractPackage'));
			Assert::same($GLOBALS['context']['upload_error'], 'Wrong file');

			unlink($zipPath);
			unset($GLOBALS['context']['upload_error']);
		}
	}

	#[Test]
	public function extractPackageAcceptsZipWithPluginFilesOnly(): void
	{
		TestEnvironment::reset();

		$zipPath = $this->createZipArchive([
			'demo/plugin-info.xml' => <<<'XML'
<?xml version="1.0"?>
<plugin id="Author:Demo"></plugin>
XML,
			'demo/readme.txt' => 'ok',
		]);

		$_REQUEST = ['get' => 1];

		$_FILES = [
			'package' => [
				'name'     => 'demo.zip',
				'type'     => 'application/zip',
				'tmp_name' => $zipPath,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$integration = new Integration();

		Assert::true($this->invokePrivate($integration, 'extractPackage'));
		Assert::true(is_file(PLUGINS_DIR . '/demo/plugin-info.xml'));
		Assert::true(is_file(PLUGINS_DIR . '/demo/readme.txt'));

		unlink($zipPath);
	}

	#[Test]
	public function extractPackageAcceptsLegacyZipMimeTypes(): void
	{
		TestEnvironment::reset();

		foreach (['application/x-zip', 'application/x-zip-compressed'] as $type) {
			$zipPath = $this->createZipArchive([
				'demo/plugin-info.xml' => '<?xml version="1.0"?><plugin id="Author:Demo"></plugin>',
			]);

			$_REQUEST = ['get' => 1];
			$_FILES = [
				'package' => [
					'name'     => 'demo.zip',
					'type'     => $type,
					'tmp_name' => $zipPath,
					'error'    => UPLOAD_ERR_OK,
				],
			];

			$integration = new Integration();

			Assert::true($this->invokePrivate($integration, 'extractPackage'));

			unlink($zipPath);
			TestEnvironment::reset();
		}
	}

	#[Test]
	public function extractPackageAcceptsArchiveWithPluginInfoAtRoot(): void
	{
		TestEnvironment::reset();

		$zipPath = $this->createZipArchive([
			'plugin-info.xml' => <<<'XML'
<?xml version="1.0"?>
<plugin id="Author:Demo"></plugin>
XML,
			'readme.txt' => 'ok',
		]);

		$_REQUEST = ['get' => 1];
		$_FILES = [
			'package' => [
				'name'     => 'demo.zip',
				'type'     => 'application/zip',
				'tmp_name' => $zipPath,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$integration = new Integration();

		Assert::true($this->invokePrivate($integration, 'extractPackage'));
		Assert::true(is_file(PLUGINS_DIR . '/demo/plugin-info.xml'));
		Assert::true(is_file(PLUGINS_DIR . '/demo/readme.txt'));

		unlink($zipPath);
	}

	#[Test]
	public function extractPackageReturnsFalseWhenRequestIsMissing(): void
	{
		TestEnvironment::reset();

		$integration = new Integration();

		Assert::false($this->invokePrivate($integration, 'extractPackage'));
		Assert::false(isset($GLOBALS['context']['upload_error']));
	}

	#[Test]
	public function extractPackageMapsUploadErrorsToUserMessages(): void
	{
		TestEnvironment::reset([
			'max_file_size' => 8 * 1024 * 1024,
		]);

		$_REQUEST = ['get' => 1];

		$cases = [
			UPLOAD_ERR_PARTIAL    => 'demo.zip partial',
			UPLOAD_ERR_INI_SIZE   => 'demo.zip too large',
			UPLOAD_ERR_CANT_WRITE => 'demo.zip write error',
			UPLOAD_ERR_FORM_SIZE  => 'Max 8',
			UPLOAD_ERR_NO_FILE    => 'No file',
			UPLOAD_ERR_EXTENSION  => 'Extension error',
			UPLOAD_ERR_NO_TMP_DIR => 'No tmp dir',
			99                    => 'Unknown upload error',
		];

		$integration = new Integration();

		foreach ($cases as $error => $message) {
			$_FILES = [
				'package' => [
					'name'     => 'demo.zip',
					'type'     => 'application/zip',
					'tmp_name' => __FILE__,
					'error'    => $error,
				],
			];

			Assert::false($this->invokePrivate($integration, 'extractPackage'));
			Assert::same($GLOBALS['context']['upload_error'], $message);
		}
	}

	#[Test]
	public function extractPackageRejectsUnsupportedMimeType(): void
	{
		TestEnvironment::reset();

		$_REQUEST = ['get' => 1];

		$_FILES = [
			'package' => [
				'name'     => 'demo.rar',
				'type'     => 'application/x-rar-compressed',
				'tmp_name' => __FILE__,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$integration = new Integration();

		Assert::false($this->invokePrivate($integration, 'extractPackage'));
		Assert::same($GLOBALS['context']['upload_error'], 'Wrong file');
	}

	#[Test]
	public function extractPackageReportsZipOpenFailure(): void
	{
		TestEnvironment::reset();

		$_REQUEST = ['get' => 1];

		$_FILES = [
			'package' => [
				'name'     => 'demo.zip',
				'type'     => 'application/zip',
				'tmp_name' => __FILE__,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$integration = new Integration();

		Assert::false($this->invokePrivate($integration, 'extractPackage'));
		Assert::true(str_starts_with($GLOBALS['context']['upload_error'], 'Failed: '));
	}

	#[Test]
	public function extractPackageRejectsArchiveWithoutPluginManifest(): void
	{
		TestEnvironment::reset();

		$zipPath = $this->createZipArchive([
			'demo/readme.txt' => 'ok',
		]);

		$_REQUEST = ['get' => 1];

		$_FILES = [
			'package' => [
				'name'     => 'demo.zip',
				'type'     => 'application/zip',
				'tmp_name' => $zipPath,
				'error'    => UPLOAD_ERR_OK,
			],
		];

		$integration = new Integration();

		Assert::false($this->invokePrivate($integration, 'extractPackage'));
		Assert::same($GLOBALS['context']['upload_error'], 'Wrong file');

		unlink($zipPath);
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
	public function handleToggleReturnsEarlyWithoutToggleRequest(): void
	{
		TestEnvironment::reset();

		$integration = new Integration();

		$this->invokePrivate($integration, 'handleToggle');

		Assert::same(SmfTestState::all('checkSession'), []);
	}

	#[Test]
	public function handleToggleRejectsPayloadWithoutPluginData(): void
	{
		TestEnvironment::reset();

		$_REQUEST = ['toggle' => 1];

		$GLOBALS['smf_json_decode_override'] = [];

		$integration = new Integration();

		try {
			$this->invokePrivate($integration, 'handleToggle');

			Assert::fail('Expected redirect exception was not thrown.');
		} catch (RuntimeException $exception) {
			Assert::same($exception->getMessage(), 'redirect:action=admin;area=plugins');
		}
	}

	#[Test]
	public function handleToggleEnablesPluginAndRunsDatabaseInstaller(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('demo', [
			'install.php' => <<<'PHP'
<?php

\Tests\Support\SmfTestState::record('database_installer', 'demo');
PHP,
		]);

		file_put_contents($GLOBALS['sourcedir'] . '/Subs-Admin.php', "<?php\n");

		$_REQUEST = ['toggle' => 1];

		$GLOBALS['context']['pl_enabled_plugins'] = [];
		$GLOBALS['context']['pl_plugins'] = [
			'demo' => ['database' => 'install.php'],
		];
		$GLOBALS['smf_json_decode_override'] = [
			'plugin' => 'demo',
			'status' => 'off',
		];

		$integration = new Integration();

		$this->invokePrivate($integration, 'handleToggle');

		Assert::same($GLOBALS['context']['pl_enabled_plugins'], ['demo']);
		Assert::same(SmfTestState::last('db_extend'), 'packages');
		Assert::same(SmfTestState::last('database_installer'), 'demo');
		Assert::same(SmfTestState::last('updateSettingsFile'), ['plugins' => 'demo']);
	}

	#[Test]
	public function handleToggleDisablesPluginAndUpdatesStoredList(): void
	{
		TestEnvironment::reset([], ['plugins' => 'alpha,demo']);

		file_put_contents($GLOBALS['sourcedir'] . '/Subs-Admin.php', "<?php\n");

		$_REQUEST = ['toggle' => 1];

		$GLOBALS['context']['pl_enabled_plugins'] = ['alpha', 'demo'];
		$GLOBALS['context']['pl_plugins'] = [
			'demo' => [],
		];
		$GLOBALS['smf_json_decode_override'] = [
			'plugin' => 'demo',
			'status' => 'on',
		];

		$integration = new Integration();

		$this->invokePrivate($integration, 'handleToggle');

		Assert::same(array_values($GLOBALS['context']['pl_enabled_plugins']), ['alpha']);
		Assert::same(SmfTestState::all('db_extend'), []);
		Assert::same(SmfTestState::last('updateSettingsFile'), ['plugins' => 'alpha']);
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
	public function handleRemoveReturnsEarlyWithoutRemoveRequest(): void
	{
		TestEnvironment::reset();

		$integration = new Integration();

		$this->invokePrivate($integration, 'handleRemove');

		Assert::same(SmfTestState::all('checkSession'), []);
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
	public function handleRemoveDeletesDisabledPluginDirectory(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('demo', [
			'plugin-info.xml' => <<<'XML'
<?xml version="1.0"?>
<plugin id="Author:Demo"></plugin>
XML,
		]);

		file_put_contents($GLOBALS['sourcedir'] . '/Subs-Package.php', "<?php\n");

		$_REQUEST = ['remove' => 1];

		$GLOBALS['context']['pl_enabled_plugins'] = [];
		$GLOBALS['smf_json_decode_override'] = ['plugin' => 'demo'];

		$integration = new Integration();

		$this->invokePrivate($integration, 'handleRemove');

		Assert::false(is_dir(PLUGINS_DIR . '/demo'));
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

		return $reflection->invoke($object, ...$arguments);
	}

	private function createZipArchive(array $files): string
	{
		$path = tempnam(sys_get_temp_dir(), 'plugin-loader-zip-');

		if ($path === false) {
			throw new RuntimeException('Unable to create temporary ZIP file.');
		}

		$zip = new ZipArchive();

		if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			throw new RuntimeException('Unable to open temporary ZIP file.');
		}

		foreach ($files as $name => $contents) {
			$zip->addFromString($name, $contents);
		}

		$zip->close();

		return $path;
	}
}
