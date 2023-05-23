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
 * @version 0.1
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
	$language_file = dirname($file, 2) . '/languages/' . $lang . '.php';

	if (is_file($language_file))
	{
		require_once $language_file;
		return;
	}

	require_once dirname($file, 2) . '/languages/english.php';
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

	$js = new MatthiasMullie\Minify\JS;
	$js->add(PLUGINS_DIR . '/' . $plugin_name . '/scripts/' . $js_name);
	$js->minify($settings['default_theme_dir'] . '/scripts/' . $plugin_name . '_' . $js_name);

	loadJavaScriptFile($plugin_name . '_' . $js_name, ['minimize' => true]);
}

function loadPluginCSS($css_name)
{
	global $settings;

	$file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];
	$plugin_name = basename(dirname($file, 2));

	$css = new MatthiasMullie\Minify\CSS;
	$css->add(PLUGINS_DIR . '/' . $plugin_name . '/styles/' . $css_name);
	$css->minify($settings['default_theme_dir'] . '/css/' . $plugin_name . '_' . $css_name);

	loadCSSFile($plugin_name . '_' . $css_name);
}

function getPluginUrl(): string
{
	$file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];
	$plugin_name = basename(dirname($file, 2));

	return PLUGINS_URL . '/' . $plugin_name;
}
