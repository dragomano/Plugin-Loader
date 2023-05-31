# Plugin Loader
[![SMF 2.1](https://img.shields.io/badge/SMF-2.1-ed6033.svg?style=flat)](https://github.com/SimpleMachines/SMF2.1)
![License](https://img.shields.io/github/license/dragomano/plugin-loader)
![Hooks only: Yes](https://img.shields.io/badge/Hooks%20only-YES-blue)
![PHP](https://img.shields.io/badge/PHP-^7.0-blue.svg?style=flat)

[Описание на русском](README.ru.md)

This concept mod is inspired by the Wedge plugin system. It adds plugin support to SMF.

Plugins are standalone modifications that are run with a single hook — *integrate_pre_load*.

The key source file of the plugin is __Integration.php__ with the class of the same name and the __hooks__ method, which is executed through the *integrate_pre_load* hook. Also in the directory of each plugin should be a file __plugin-info.xml__, which contains the key data of the plugin:

	* name
	* description
	* plugin version
	* author name
	* link to the author's website (optional)
	* email of the author (optional)
	* the license used
	* link to the plugin site

Plugins are turned on and off at the touch of a button. To install, simply place the plugin folder with the correct structure in the __Plugins__ directory.

![](preview.png)

The list of currently active plugins is stored in the global variable __$plugins__ in the _Settings.php_ file. To disable a problem plugin, just _remove its name from the $plugins_ variable, or _rename the plugin folder_, or _rename the Integration.php_ file of the plugin, or _rename/remove the hooks_ method in the plugin class.

## Example plugin structure

```
example_plugin/
	images/
		example.png
		index.php
	languages/
		english.php
		index.php
		russian.php
	sources/
		index.php
		Integration.php
	templates/
		index.php
		Example.template.php
	scripts/
		index.php
		example.js
	styles/
		index.php
		example.css
	index.php
	license.txt
	plugin-info.xml
```

## Example plugin-info.xml file

```xml
<?xml version="1.0" standalone="yes" ?>
<plugin id="Author:Example">
	<name>Example</name>
	<description>
		<english>Description...</english>
		<russian>Описание...</russian>
	</description>
	<version>0.1</version>
	<author email="noreply@site.com" url="https://author-site.com">Author</author>
	<license url="https://license-site.com">License name</license>
	<website>https://plugin-site.com</website>
</plugin>
```

Plugins that require creation of tables in the database for their work must contain a node `<database>file_name.php</database>` in __plugin-info.xml__.
In the specified file, you can place a script to create the necessary tables when the plugin is enabled, if they have not yet been created.

## Example Integration.php file

```php
<?php

/**
 * Integration.php
 *
 * @package Example
 * @link https://plugin-site.com
 * @author Author https://author-site.com
 * @copyright 2023 Author
 * @license https://opensource.org/licenses/MIT The MIT License
 *
 * @version 0.1
 */

if (!defined('SMF'))
	die('No direct access...');

class Integration
{
	public function hooks(): void
	{
		add_integration_function('integrate_load_theme', __CLASS__ . '::loadTheme#', false, __FILE__);
		add_integration_function('integrate_menu_buttons', __CLASS__ . '::menuButtons#', false, __FILE__);
	}

	public function loadTheme(): void
	{
		loadPluginLanguage();

		// Your code
	}

	public function menuButtons($buttons): void
	{
		// var_dump($buttons);
	}
}

```

As you can see, all the hooks required by the plugin are listed in the `hooks` method, which is executed if the plugin is enabled.

## Auxiliary functions

The following functions are provided to work with plugin files:

* `loadPluginSource($source_name)` - plugging PHP file `$source_name` from subdirectory `sources` of the current plugin
* `loadPluginLanguage($lang = '')` - plugging PHP language file `$lang` from subdirectory `languages` of the current plugin (by default `$lang = $context['user']['language']`)
* `loadPluginTemplate($template_name)` - plugging PHP template file `$template_name` from subdirectory `templates` of the current plugin
* `loadPluginJS($js_name)` - plugging JS-file `$js_name` from subdirectory `scripts` of the current plugin
* `loadPluginCSS($css_name)` - plugging CSS file `$css_name` from subdirectory `styles` of the current plugin
* `getPluginUrl()` - returns URL to the directory of the current plugin

## Examples of working plugins

* [Profile Starsigns](https://e.pcloud.link/publink/show?code=XZjmFuZ0SzABxCOdluGxcu5nu1Ls49GAybX)
