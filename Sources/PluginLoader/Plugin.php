<?php

/**
 * Plugin.php
 *
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 *
 * @version 0.5
 */

namespace Bugo\PluginLoader;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;

if (!defined('SMF'))
	die('No direct access...');

abstract class Plugin
{
	protected string $name;

	protected array $txt;

	public function __construct()
	{
		$this->name = $this->getName();
	}

	abstract public function getName();

	abstract public function hooks();

	protected function loadLanguage(string $lang_name = ''): void
	{
		global $user_info;

		$lang = empty($lang_name) ? $user_info['language'] : $lang_name;
		$languages = array_unique(['english', $lang]);

		$pluginLanguages = [];
		foreach ($languages as $language) {
			$langFile = $this->getPath() . '/languages/' . $language . '.php';
			$pluginLanguages[$language] = is_file($langFile) ? require_once $langFile : [];
		}

		if (is_array($pluginLanguages['english']))
			$this->txt = array_merge($pluginLanguages['english'], $pluginLanguages[$lang]);
	}

	protected function loadTemplate(string $template_name): void
	{
		require_once $this->getPath() . '/templates/' . $template_name . '.template.php';
	}

	protected function loadCSS(string $css_name, string $extension = '.css'): void
	{
		global $settings;

		$css_name = str_replace($extension, '', $css_name) . $extension;

		$source_file = $this->getPath() . '/styles/' . $css_name;
		$target_file = $settings['default_theme_dir'] . '/css/' . $this->name . '_' . $css_name;

		if (!is_file($target_file) || filemtime($target_file) < filemtime($source_file)) {
			$css = new CSS;
			$css->add($source_file);
			$css->minify($target_file);
		}

		loadCSSFile($this->name . '_' . $css_name);
	}

	protected function loadJS(string $js_name, string $extension = '.js'): void
	{
		global $settings;

		$js_name = str_replace($extension, '', $js_name) . $extension;

		$source_file = $this->getPath() . '/scripts/' . $js_name;
		$target_file = $settings['default_theme_dir'] . '/scripts/' . $this->name . '_' . $js_name;

		if (!is_file($target_file) || filemtime($target_file) < filemtime($source_file)) {
			$js = new JS;
			$js->add($source_file);
			$js->minify($target_file);
		}

		loadJavaScriptFile($this->name . '_' . $js_name, ['minimize' => true]);
	}

	protected function loadSource(string $source_name): void
	{
		require_once $this->getPath() . '/sources/' . $source_name . '.php';
	}

	protected function getUrl(string $sub_directory = ''): string
	{
		return PLUGINS_URL . '/' . $this->name . '/' . ($sub_directory ? $sub_directory . '/' : '');
	}

	protected function getPath(): string
	{
		return PLUGINS_DIR . DIRECTORY_SEPARATOR . $this->name;
	}

	protected function getSettings(string $key, $default = null)
	{
		$settings = parse_ini_file($this->getPath() . '/settings.ini');

		if (empty($key)) {
			return $settings;
		}

		return $settings[$key] ?? $default;
	}
}
