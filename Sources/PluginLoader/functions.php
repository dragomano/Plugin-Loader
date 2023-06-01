<?php

/**
 * functions.php
 *
 * @package Plugin Loader
 * @link https://github.com/dragomano/Plugin-Loader
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023 Bugo
 * @license https://opensource.org/licenses/BSD-3-Clause The 3-Clause BSD License
 *
 * @version 0.3
 */

if (!defined('SMF'))
	die('No direct access...');

function loadPluginSource($source_name)
{
	$file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];

	require_once dirname($file, 2) . '/sources/' . $source_name . '.php';
}

function loadPluginLanguage($lang = '')
{
	global $user_info, $txt;

	$lang = empty($lang) ? $user_info['language'] : $lang;
	$file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];
	$plugin_name = basename(dirname($file, 2)) . '_plugin';

	if (isset($txt[$plugin_name]))
		return;

	$languages = array_unique(['english', $lang]);

	$pluginLanguages = [];
	foreach ($languages as $language) {
		$langFile = dirname($file, 2) . '/languages/' . $language . '.php';
		$pluginLanguages[$language] = is_file($langFile) ? require_once $langFile : [];
	}

	if (is_array($pluginLanguages['english']))
		$txt[$plugin_name] = array_merge($pluginLanguages['english'], $pluginLanguages[$lang]);
}

function loadPluginTemplate($template_name)
{
	$file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];

	require_once dirname($file, 2) . '/templates/' . $template_name . '.template.php';
}

function loadPluginJS($js_name)
{
	global $settings;

	$file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];
	$plugin_name = basename(dirname($file, 2));

	$source_file = PLUGINS_DIR . '/' . $plugin_name . '/scripts/' . $js_name;
	$target_file = $settings['default_theme_dir'] . '/scripts/' . $plugin_name . '_' . $js_name;

	if (!is_file($target_file) || filemtime($target_file) < filemtime($source_file))
	{
		$js = new MatthiasMullie\Minify\JS;
		$js->add($source_file);
		$js->minify($target_file);
	}

	loadJavaScriptFile($plugin_name . '_' . $js_name, ['minimize' => true]);
}

function loadPluginCSS($css_name)
{
	global $settings;

	$file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];
	$plugin_name = basename(dirname($file, 2));

	$source_file = PLUGINS_DIR . '/' . $plugin_name . '/styles/' . $css_name;
	$target_file = $settings['default_theme_dir'] . '/css/' . $plugin_name . '_' . $css_name;

	if (!is_file($target_file) || filemtime($target_file) < filemtime($source_file))
	{
		$css = new MatthiasMullie\Minify\CSS;
		$css->add($source_file);
		$css->minify($target_file);
	}

	loadCSSFile($plugin_name . '_' . $css_name);
}

function getPluginUrl(): string
{
	$file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];
	$plugin_name = basename(dirname($file, 2));

	return PLUGINS_URL . '/' . $plugin_name;
}
