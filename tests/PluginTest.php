<?php

declare(strict_types=1);

namespace Tests;

use Bugo\PluginLoader\Plugin;
use Testo\Assert;
use Testo\Test;
use Tests\Support\SmfTestState;
use Tests\Support\TestEnvironment;

final class PluginTest
{
	#[Test]
	public function loadLanguageMergesEnglishAndCurrentLocale(): void
	{
		TestEnvironment::reset([
			'user' => ['language' => 'russian'],
		]);

		TestEnvironment::createPlugin('plugin-test', [
			'languages/english.php' => <<<'PHP'
<?php

return [
	'english_only' => 'English only',
	'shared' => 'English shared',
];
PHP,
			'languages/russian.php' => <<<'PHP'
<?php

return [
	'shared' => 'Russian shared',
	'local_only' => 'Russian only',
];
PHP,
		]);

		$plugin = new TestablePlugin();
		$plugin->loadPluginLanguage();

		Assert::same($GLOBALS['txt']['english_only'], 'English only');
		Assert::same($GLOBALS['txt']['shared'], 'Russian shared');
		Assert::same($GLOBALS['txt']['local_only'], 'Russian only');
	}

	#[Test]
	public function getSettingsReturnsDefaultsWhenFileIsMissing(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('plugin-test', []);

		$plugin = new TestablePlugin();

		Assert::same($plugin->allSettings(), []);
		Assert::same($plugin->setting('missing', 'fallback'), 'fallback');
	}

	#[Test]
	public function getSettingsReadsIniValues(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('plugin-test', [
			'settings.ini' => <<<'INI'
enabled = 1
title = "Demo plugin"
INI,
		]);

		$plugin = new TestablePlugin();

		Assert::same($plugin->allSettings(), [
			'enabled' => '1',
			'title'   => 'Demo plugin',
		]);

		Assert::same($plugin->setting('title'), 'Demo plugin');
	}

	#[Test]
	public function loadTemplateRequiresPluginTemplateFile(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('plugin-test', [
			'templates/demo.template.php' => <<<'PHP'
<?php

\Tests\Support\SmfTestState::record('templateRequire', 'demo');
PHP,
		]);

		$plugin = new TestablePlugin();
		$plugin->loadPluginTemplate('demo');

		Assert::same(SmfTestState::last('templateRequire'), 'demo');
	}

	#[Test]
	public function loadSourceRequiresPluginSourceFile(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('plugin-test', [
			'sources/demo.php' => <<<'PHP'
<?php

\Tests\Support\SmfTestState::record('sourceRequire', 'demo');
PHP,
		]);

		$plugin = new TestablePlugin();
		$plugin->loadPluginSource('demo');

		Assert::same(SmfTestState::last('sourceRequire'), 'demo');
	}

	#[Test]
	public function loadCSSMinifiesAndRegistersStylesheet(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('plugin-test', [
			'styles/demo.css' => 'body { color: red; }',
		]);

		$plugin = new TestablePlugin();
		$plugin->loadPluginCss('demo');

		Assert::true(is_file(TestEnvironment::themeDir() . '/css/plugin-test_demo.css'));
		Assert::same(SmfTestState::last('loadCSSFile'), ['plugin-test_demo.css', []]);
	}

	#[Test]
	public function loadJSMinifiesAndRegistersScript(): void
	{
		TestEnvironment::reset();
		TestEnvironment::createPlugin('plugin-test', [
			'scripts/demo.js' => 'const demo = 1;',
		]);

		$plugin = new TestablePlugin();
		$plugin->loadPluginJs('demo');

		Assert::true(is_file(TestEnvironment::themeDir() . '/scripts/plugin-test_demo.js'));
		Assert::same(SmfTestState::last('loadJavaScriptFile'), [
			'plugin-test_demo.js',
			['minimize' => true],
		]);
	}

	#[Test]
	public function getUrlAndPathUsePluginConstants(): void
	{
		TestEnvironment::reset();

		$plugin = new TestablePlugin();

		Assert::same($plugin->pluginUrl(), 'https://example.test/Plugins/plugin-test/');
		Assert::same($plugin->pluginUrl('assets'), 'https://example.test/Plugins/plugin-test/assets/');
		Assert::same($plugin->pluginPath(), PLUGINS_DIR . DIRECTORY_SEPARATOR . 'plugin-test');
	}
}

final class TestablePlugin extends Plugin
{
	protected const NAME = 'plugin-test';

	public function loadPluginLanguage(string $language = ''): void
	{
		$this->loadLanguage($language);
	}

	public function allSettings(): array
	{
		return $this->getSettings();
	}

	public function setting(string $key, mixed $default = null): mixed
	{
		return $this->getSettings($key, $default);
	}

	public function loadPluginTemplate(string $template): void
	{
		$this->loadTemplate($template);
	}

	public function loadPluginCss(string $name, string $extension = '.css'): void
	{
		$this->loadCSS($name, $extension);
	}

	public function loadPluginJs(string $name, string $extension = '.js'): void
	{
		$this->loadJS($name, $extension);
	}

	public function loadPluginSource(string $source): void
	{
		$this->loadSource($source);
	}

	public function pluginUrl(string $subDirectory = ''): string
	{
		return $this->getUrl($subDirectory);
	}

	public function pluginPath(): string
	{
		return $this->getPath();
	}
}
