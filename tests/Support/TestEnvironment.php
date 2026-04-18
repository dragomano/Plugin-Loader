<?php

declare(strict_types=1);

namespace Tests\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TestEnvironment
{
	public static function rootDir(): string
	{
		return dirname(PLUGINS_DIR);
	}

	public static function themeDir(): string
	{
		return self::rootDir() . '/Theme';
	}

	public static function reset(array $contextOverrides = [], array $globalOverrides = []): void
	{
		SmfTestState::reset();

		self::removeDir(self::rootDir());

		mkdir(PLUGINS_DIR, 0777, true);
		mkdir(self::themeDir() . '/css', 0777, true);
		mkdir(self::themeDir() . '/scripts', 0777, true);
		mkdir(self::rootDir() . '/Sources', 0777, true);

		$GLOBALS['context'] = array_replace([
			'user'            => ['language' => 'english'],
			'admin_menu_name' => 'admin',
			'sub_action'      => 'browse',
			'session_id'      => 'session-id',
			'session_var'     => 'session_var',
			'character_set'   => 'UTF-8',
			'admin'           => [],
		], $contextOverrides);

		$GLOBALS['user_info'] = [
			'language' => $GLOBALS['context']['user']['language'],
		];

		$GLOBALS['memberContext'] = [];

		$GLOBALS['settings'] = ['default_theme_dir' => self::themeDir()];

		$GLOBALS['txt'] = [
			'pl_title'                          => 'Plugin Manager',
			'pl_browse'                         => 'Browse plugins',
			'pl_upload'                         => 'Add plugins',
			'pl_browse_desc'                    => 'Browse plugins',
			'pl_upload_desc'                    => 'Upload plugins',
			'not_applicable'                    => 'N/A',
			'download_success'                  => 'Done',
			'pl_upload_error_partial'           => '%s partial',
			'pl_upload_error_ini_size'          => '%s too large',
			'pl_upload_error_cant_write'        => '%s write error',
			'pl_upload_error_size'              => 'Max %s',
			'pl_upload_error_upload_no_file'    => 'No file',
			'pl_upload_error_upload_extension'  => 'Extension error',
			'pl_upload_error_upload_no_tmp_dir' => 'No tmp dir',
			'pl_upload_error_unknown'           => 'Unknown upload error',
			'pl_upload_wrong_file'              => 'Wrong file',
			'pl_upload_failed'                  => 'Failed: %d',
		];

		$GLOBALS['smcFunc'] = [
			'htmlspecialchars' => htmlspecialchars(...),
		];

		$GLOBALS['plugins']   = '';
		$GLOBALS['sourcedir'] = self::rootDir() . '/Sources';
		$GLOBALS['boarddir']  = self::rootDir();
		$GLOBALS['boardurl']  = 'https://example.test';

		unset($GLOBALS['smf_json_decode_override']);

		$_REQUEST = [];
		$_FILES   = [];

		foreach ($globalOverrides as $name => $value) {
			$GLOBALS[$name] = $value;
		}
	}

	public static function createPlugin(string $name, array $files): string
	{
		$pluginDir = PLUGINS_DIR . '/' . $name;

		mkdir($pluginDir, 0777, true);

		foreach ($files as $relativePath => $contents) {
			$fullPath  = $pluginDir . '/' . $relativePath;
			$directory = dirname($fullPath);

			if (! is_dir($directory)) {
				mkdir($directory, 0777, true);
			}

			file_put_contents($fullPath, $contents);
		}

		return $pluginDir;
	}

	private static function removeDir(string $directory): void
	{
		if (! is_dir($directory)) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST,
		);

		foreach ($iterator as $item) {
			$item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
		}

		rmdir($directory);
	}
}
