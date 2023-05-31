<?php /** @noinspection PhpComposerExtensionStubsInspection */

/**
 * Integration.php
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

use SimpleXMLElement;
use ZipArchive;

if (!defined('SMF'))
	die('No direct access...');

/**
 * Generated by Simple Mod Maker
 */
class Integration
{
	public function hooks()
	{
		add_integration_function('integrate_update_settings_file', __CLASS__ . '::updateSettingsFile#', false, __FILE__);
		add_integration_function('integrate_admin_areas', __CLASS__ . '::adminAreas#', false, __FILE__);
	}

	public function updateSettingsFile(array &$settings_defs)
	{
		$settings_defs['plugins'] = [
			'text' => implode("\n", [
				'/**',
				' * Enabled plugins',
				' *',
				' * @var string',
				' */',
			]),
			'default' => '',
			'type' => 'string'
		];
	}

	public function adminAreas(array &$admin_areas)
	{
		global $txt;

		loadLanguage('PluginLoader/');

		$admin_areas['forum']['areas']['plugins'] = [
			'label'       => $txt['pl_title'],
			'function'    => [$this, 'main'],
			'permission'  => ['admin_forum'],
			'icon'        => 'modifications',
			'subsections' => [
				'browse' => [$txt['pl_browse']],
				'upload' => [$txt['pl_upload']],
			],
		];
	}

	public function main()
	{
		global $context, $txt, $sourcedir;

		$subActions = [
			'browse' => [$this, 'browseList'],
			'upload' => [$this, 'uploadArea'],
		];

		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['pl_title'],
			'tabs' => [
				'browse' => [
					'description' => $txt['pl_browse_desc'],
				],
				'upload' => [
					'description' => $txt['pl_upload_desc'],
				]
			]
		];

		require_once $sourcedir . '/ManageSettings.php';

		loadGeneralSettingParameters($subActions, 'browse');

		call_helper($subActions[$context['sub_action']]);
	}

	public function browseList()
	{
		global $context, $txt, $sourcedir;

		$context['page_title'] = $txt['pl_title'];

		loadCSSFile('plugin_loader.css');
		loadJavaScriptFile('plugin_loader.js', ['minimize' => true]);

		loadTemplate('PluginLoader');

		$context['sub_template'] = 'main';

		$this->preparePluginList();

		if (isset($_REQUEST['toggle']) || isset($_REQUEST['remove']))
		{
			$input = file_get_contents('php://input');
			$data  = smf_json_decode($input, true) ?? [];

			if (empty($data) || empty($data['plugin']))
				redirectexit('action=admin;area=plugins');

			if (isset($data['status']))
			{
				if ($data['status'] === 'on')
					$context['pl_enabled_plugins'] = array_filter($context['pl_enabled_plugins'], function ($item) use ($data) {
						return $item !== $data['plugin'];
					});
				else
					$context['pl_enabled_plugins'][] = $data['plugin'];

				sort($context['pl_enabled_plugins']);

				require_once $sourcedir . '/Subs-Admin.php';

				updateSettingsFile(['plugins' => implode(',', $context['pl_enabled_plugins'])]);

				return;
			}

			require_once $sourcedir . '/Subs-Package.php';

			deltree(PLUGINS_DIR . '/' . $data['plugin']);
		}
	}

	public function uploadArea()
	{
		global $context, $txt;

		loadLanguage('Packages');

		loadCSSFile('plugin_loader.css');

		loadTemplate('PluginLoader');

		$context['page_title'] = $txt['pl_title'] . ' - ' . $txt['pl_upload'];

		$context['max_file_size'] = memoryReturnBytes(ini_get('upload_max_filesize'));

		$context['upload_success'] = $this->extractPackage() ? $txt['download_success'] : false;

		$context['sub_template'] = 'upload';
	}

	private function preparePluginList()
	{
		global $context, $plugins;

		$context['pl_enabled_plugins'] = empty($plugins) ? [] : explode(',', $plugins);
		$context['pl_plugins'] = [];

		$plugins = glob(PLUGINS_DIR . '/**/plugin-info.xml', GLOB_BRACE);

		foreach ($plugins as $plugin)
		{
			if (is_file($plugin))
			{
				$id = basename(dirname($plugin));
				$content = file_get_contents($plugin);

				if (empty($content))
				{
					$context['pl_plugins'][$id] = false;
					continue;
				}

				$content = preg_replace('~\s*<(!DOCTYPE|xsl)[^>]+?>\s*~i', '', $content);
				$xmldata = simplexml_load_string($content);
				$context['pl_plugins'][$id] = $this->escapeArray($this->xmlToArray($xmldata));
			}
		}
	}

	private function escapeArray(array $data): array
	{
		global $smcFunc;

		foreach ($data as $key => $value)
		{
			if (is_array($value))
				$data[$key] = $this->escapeArray($value);
			else
				$data[$key] = $smcFunc['htmlspecialchars']($value, ENT_QUOTES);
		}

		return $data;
	}

	private function xmlToArray(SimpleXMLElement $xml): array
	{
		$parser = function (SimpleXMLElement $xml, array $collection = []) use (&$parser) {
			$nodes = $xml->children();
			$attributes = $xml->attributes();

			if (0 !== count($attributes))
			{
				foreach ($attributes as $attrName => $attrValue)
					$collection['@attributes'][$attrName] = strval($attrValue);
			}

			if (0 === $nodes->count())
			{
				if ($xml->attributes())
					$collection['value'] = strval($xml);
				else
					$collection = strval($xml);

				return $collection;
			}

			foreach ($nodes as $nodeName => $nodeValue)
			{
				if (count($nodeValue->xpath('../' . $nodeName)) < 2)
				{
					$collection[$nodeName] = $parser($nodeValue);
					continue;
				}

				$collection[$nodeName][] = $parser($nodeValue);
			}

			return $collection;
		};

		return $parser($xml);
	}

	private function extractPackage(): bool
	{
		global $txt, $context;

		if (!isset($_REQUEST['get']))
			return false;

		$package = $_FILES['package'];

		if ($package['error'] !== UPLOAD_ERR_OK)
		{
			$errorMessages = [
				UPLOAD_ERR_PARTIAL    => sprintf($txt['pl_upload_error_partial'], $package['name']),
				UPLOAD_ERR_INI_SIZE   => sprintf($txt['pl_upload_error_ini_size'], $package['name']),
				UPLOAD_ERR_CANT_WRITE => sprintf($txt['pl_upload_error_cant_write'], $package['name']),
				UPLOAD_ERR_FORM_SIZE  => sprintf($txt['pl_upload_error_size'], $context['max_file_size'] / 1024 / 1024),
				UPLOAD_ERR_NO_FILE    => $txt['pl_upload_error_upload_no_file'],
				UPLOAD_ERR_EXTENSION  => $txt['pl_upload_error_upload_extension'],
				UPLOAD_ERR_NO_TMP_DIR => $txt['pl_upload_error_upload_no_tmp_dir'],
			];

			$context['upload_error'] = $errorMessages[$package['error']] ?? $txt['pl_upload_error_unknown'];

			return false;
		}

		switch ($package['type'])
		{
			case 'application/zip':
			case 'application/x-zip':
			case 'application/x-zip-compressed':
				break;

			default:
				$context['upload_error'] = $txt['pl_upload_wrong_file'];
				return false;
		}

		$zip = new ZipArchive();
		$result = $zip->open($package['tmp_name']);

		if ($result === true)
		{
			$plugin = pathinfo($package['name'], PATHINFO_FILENAME);

			if ($zip->locateName($plugin . '/plugin-info.xml') !== false)
				return $zip->extractTo(PLUGINS_DIR);
			elseif ($zip->locateName('plugin-info.xml') !== false)
				return $zip->extractTo(PLUGINS_DIR . DIRECTORY_SEPARATOR . $plugin);

			$context['upload_error'] = $txt['pl_upload_wrong_file'];
		}
		else
		{
			$context['upload_error'] = sprintf($txt['pl_upload_failed'], $result);
		}

		return false;
	}
}
