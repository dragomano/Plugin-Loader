<?php

/**
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2025 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 */

namespace Bugo\PluginLoader;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;

use function array_merge;
use function array_unique;
use function filemtime;
use function is_array;
use function is_file;
use function parse_ini_file;
use function str_replace;

if (! defined('SMF'))
	die('No direct access...');

abstract class Plugin
{
	use HasInvoke;

	protected const NAME = '';

	protected array $context;

	protected ?array $user_info;

	protected ?array $memberContext;

	protected array $settings;

	protected ?array $txt;

	public function __construct()
	{
		foreach (['context', 'user_info', 'memberContext', 'settings', 'txt'] as $f) {
			$this->{$f} = &$GLOBALS[$f];
		}
	}

	protected function loadLanguage(string $lang_name = ''): void
	{
		$lang = empty($lang_name) ? $this->user_info['language'] : $lang_name;
		$languages = array_unique(['english', $lang]);

		$pluginLanguages = [];
		foreach ($languages as $language) {
			$langFile = $this->getPath() . '/languages/' . $language . '.php';
			$pluginLanguages[$language] = is_file($langFile) ? require_once $langFile : [];
		}

		if (is_array($pluginLanguages['english'])) {
			$this->txt = array_merge($this->txt, $pluginLanguages['english'], $pluginLanguages[$lang]);
		}
	}

	protected function loadTemplate(string $template_name): void
	{
		require_once $this->getPath() . '/templates/' . $template_name . '.template.php';
	}

	protected function loadCSS(string $css_name, string $extension = '.css'): void
	{
		$css_name = str_replace($extension, '', $css_name) . $extension;

		$source_file = $this->getPath() . '/styles/' . $css_name;
		$target_file = $this->settings['default_theme_dir'] . '/css/' . static::NAME . '_' . $css_name;

		if (! is_file($target_file) || filemtime($target_file) < filemtime($source_file)) {
			$css = new CSS;
			$css->add($source_file);
			$css->minify($target_file);
		}

		loadCSSFile(static::NAME . '_' . $css_name);
	}

	protected function loadJS(string $js_name, string $extension = '.js'): void
	{
		$js_name = str_replace($extension, '', $js_name) . $extension;

		$source_file = $this->getPath() . '/scripts/' . $js_name;
		$target_file = $this->settings['default_theme_dir'] . '/scripts/' . static::NAME . '_' . $js_name;

		if (! is_file($target_file) || filemtime($target_file) < filemtime($source_file)) {
			$js = new JS;
			$js->add($source_file);
			$js->minify($target_file);
		}

		loadJavaScriptFile(static::NAME . '_' . $js_name, ['minimize' => true]);
	}

	protected function loadSource(string $source_name): void
	{
		require_once $this->getPath() . '/sources/' . $source_name . '.php';
	}

	protected function getUrl(string $sub_directory = ''): string
	{
		return PLUGINS_URL . '/' . static::NAME . '/' . ($sub_directory ? $sub_directory . '/' : '');
	}

	protected function getPath(): string
	{
		return PLUGINS_DIR . DIRECTORY_SEPARATOR . static::NAME;
	}

	protected function getSettings(string $key = '', $default = null)
	{
		$settings = parse_ini_file($this->getPath() . '/settings.ini');

		if ($key === '') {
			return $settings;
		}

		return $settings[$key] ?? $default;
	}
}
