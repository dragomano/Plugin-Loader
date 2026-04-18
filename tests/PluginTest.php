<?php

declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/Support/Bootstrap.php';

use Bugo\PluginLoader\Plugin;
use Testo\Assert;
use Testo\Test;
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
}
