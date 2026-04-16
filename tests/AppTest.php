<?php

declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/Support/Bootstrap.php';

use RuntimeException;
use Testo\Assert;
use Testo\Test;
use Tests\Support\TestEnvironment;

final class AppTest
{
	#[Test]
	public function appBootstrapsIntegrationAndEnabledPlugin(): void
	{
		$result = $this->runApp('INDEX', 'demo');

		Assert::same($result['plugins_dir'], TestEnvironment::rootDir() . '/app-case-index/Plugins');
		Assert::same($result['plugins_url'], 'https://example.test/Plugins');
		Assert::same(array_column($result['hooks'], 'hook'), [
			'integrate_update_settings_file',
			'integrate_admin_areas',
			'integrate_test_plugin',
		]);
	}

	#[Test]
	public function appSkipsPluginBootInBackgroundMode(): void
	{
		$result = $this->runApp('BACKGROUND', 'demo');

		Assert::same(array_column($result['hooks'], 'hook'), [
			'integrate_update_settings_file',
			'integrate_admin_areas',
		]);
	}

	private function runApp(string $smfMode, string $plugins): array
	{
		$caseRoot = TestEnvironment::rootDir() . '/app-case-' . strtolower($smfMode);

		$this->prepareAppCase($caseRoot);

		$scriptPath = $caseRoot . '/run-app.php';
		$appPath    = dirname(__DIR__) . '/src/Sources/PluginLoader/app.php';

		file_put_contents($scriptPath, strtr(<<<'PHP'
<?php

define('SMF', '__SMF__');

$boarddir = '__BOARDDIR__';
$boardurl = 'https://example.test';
$plugins = '__PLUGINS__';

$GLOBALS['boarddir'] = $boarddir;
$GLOBALS['boardurl'] = $boardurl;
$GLOBALS['plugins'] = $plugins;
$GLOBALS['sourcedir'] = $boarddir . '/Sources';
$GLOBALS['context'] = [];
$GLOBALS['user_info'] = ['language' => 'english'];
$GLOBALS['memberContext'] = [];
$GLOBALS['settings'] = [];
$GLOBALS['txt'] = [];
$GLOBALS['smcFunc'] = [];

$hooks = [];

function add_integration_function(string $hook, string $callback, bool $permanent, string $file): void
{
	global $hooks;

	$hooks[] = [
		'hook' => $hook,
		'callback' => $callback,
		'permanent' => $permanent,
		'file' => $file,
	];
}

require '__APP__';

echo json_encode([
	'plugins_dir' => PLUGINS_DIR,
	'plugins_url' => PLUGINS_URL,
	'hooks' => $hooks,
], JSON_THROW_ON_ERROR);
PHP, [
			'__SMF__'      => $smfMode,
			'__BOARDDIR__' => addslashes($caseRoot),
			'__PLUGINS__'  => $plugins,
			'__APP__'      => addslashes($appPath),
		]));

		$output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($scriptPath));

		if (! is_string($output)) {
			throw new RuntimeException('Failed to execute app.php in a subprocess.');
		}

		/** @var array{plugins_dir: string, plugins_url: string, hooks: list<array{hook: string, callback: string, permanent: bool, file: string}>} $result */
		$result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

		return $result;
	}

	private function prepareAppCase(string $caseRoot): void
	{
		$this->removeDir($caseRoot);

		mkdir($caseRoot . '/Plugins/demo/sources', 0777, true);
		mkdir($caseRoot . '/Sources', 0777, true);

		file_put_contents($caseRoot . '/Plugins/demo/sources/plugin.php', <<<'PHP'
<?php

use Bugo\PluginLoader\Attributes\Hook;
use Bugo\PluginLoader\Plugin;

return new class extends Plugin
{
	protected const NAME = 'demo';

	#[Hook('integrate_test_plugin')]
	public function boot(): void
	{
	}
};
PHP);
	}

	private function removeDir(string $directory): void
	{
		if (! is_dir($directory)) {
			return;
		}

		$items = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST,
		);

		foreach ($items as $item) {
			$item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
		}

		rmdir($directory);
	}
}
