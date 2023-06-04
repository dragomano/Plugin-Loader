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
 * @version 0.3
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

	public function loadLanguage($lang = '')
	{
		global $user_info;

		$lang = empty($lang) ? $user_info['language'] : $lang;
		$languages = array_unique(['english', $lang]);

		$pluginLanguages = [];
		foreach ($languages as $language) {
			$langFile = PLUGINS_DIR . DIRECTORY_SEPARATOR . $this->name . '/languages/' . $language . '.php';
			$pluginLanguages[$language] = is_file($langFile) ? require_once $langFile : [];
		}

		if (is_array($pluginLanguages['english']))
			$this->txt = array_merge($pluginLanguages['english'], $pluginLanguages[$lang]);
	}

	public function loadTemplate($template_name)
	{
		require_once PLUGINS_DIR . DIRECTORY_SEPARATOR . $this->name . '/templates/' . $template_name . '.template.php';
	}

	public function loadCSS($css_name)
	{
		global $settings;

		$css_name .= '.css';

		$source_file = PLUGINS_DIR . DIRECTORY_SEPARATOR . $this->name . '/styles/' . $css_name;
		$target_file = $settings['default_theme_dir'] . '/css/' . $this->name . '_' . $css_name;

		if (!is_file($target_file) || filemtime($target_file) < filemtime($source_file))
		{
			$css = new CSS;
			$css->add($source_file);
			$css->minify($target_file);
		}

		loadCSSFile($this->name . '_' . $css_name);
	}

	public function loadJS($js_name)
	{
		global $settings;

		$js_name .= '.js';

		$source_file = PLUGINS_DIR . DIRECTORY_SEPARATOR . $this->name . '/scripts/' . $js_name;
		$target_file = $settings['default_theme_dir'] . '/scripts/' . $this->name . '_' . $js_name;

		if (!is_file($target_file) || filemtime($target_file) < filemtime($source_file))
		{
			$js = new JS;
			$js->add($source_file);
			$js->minify($target_file);
		}

		loadJavaScriptFile($this->name . '_' . $js_name, ['minimize' => true]);
	}

	public function loadSource($source_name)
	{
		require_once PLUGINS_DIR . DIRECTORY_SEPARATOR . $this->name . '/sources/' . $source_name . '.php';
	}

	public function getUrl(string $sub_directory = ''): string
	{
		return PLUGINS_URL . '/' . $this->name . '/' . ($sub_directory ? $sub_directory . '/' : '');
	}
}
