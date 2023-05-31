# Plugin Loader
[![SMF 2.1](https://img.shields.io/badge/SMF-2.1-ed6033.svg?style=flat)](https://github.com/SimpleMachines/SMF2.1)
![License](https://img.shields.io/github/license/dragomano/plugin-loader)
![Hooks only: Yes](https://img.shields.io/badge/Hooks%20only-YES-blue)
![PHP](https://img.shields.io/badge/PHP-^7.0-blue.svg?style=flat)
[![Crowdin](https://badges.crowdin.net/plugin-loader/localized.svg)](https://crowdin.com/project/plugin-loader)

[Описание на английском](README.md)

Этот концепт-мод вдохновлён системой плагинов Wedge. Он добавляет поддержку плагинов в SMF.

Плагины — это автономные модификации, запускаемые с помощью одного единственного хука — *integrate_pre_load*.

Ключевым source-файлом у плагина является __Integration.php__ с одноименным классом внутри и методом __hooks__, выполняемым через хук *integrate_pre_load*. Также в директории каждого плагина должен находиться файл __plugin-info.xml__, содержащий ключевые данные плагина:

	* название
	* описание
	* версия плагина
	* имя автора
	* ссылка на сайт автора (не обязательно)
	* имейл автора (не обязательно)
	* используемая лицензия
	* ссылка на сайт плагина

Плагины включатся и выключаются одним нажатием кнопки. Для установки достаточно поместить папку плагина с правильной структурой в директорию __Plugins__.

![](preview.png)

Список текущих активных плагинов форума хранится в глобальной переменной __$plugins__ в файле _Settings.php_. Для отключения проблемного плагина достаточно _удалить его название из переменной $plugins_, либо _переименовать папку плагина_, либо _переименовать файл Integration.php_ плагина, либо _переименовать/убрать метод hooks_ в классе плагина.

## Пример структуры плагина

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

## Пример файла plugin-info.xml

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

Плагины, требующие для своей работы создание таблиц в базе данных, должны содержать узел `<database>имя_файла.php</database>` в __plugin-info.xml__.
В указанном файле можно разместить скрипт создания нужных таблиц при включении плагина, если они ещё не созданы.

## Пример файла Integration.php

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

Как видите, все требуемые плагином хуки перечисляются в методе `hooks`, который выполняется, если плагин включён.

## Вспомогательные функции

Для работы с файлами плагинов предусмотрены следующие функции:

* `loadPluginSource($source_name)` - подключение PHP-файла `$source_name` из поддиректории `sources` текущего плагина
* `loadPluginLanguage($lang = '')` - подключение языкового PHP-файла `$lang` из поддиректории `languages` текущего плагина (по умолчанию `$lang = $context['user']['language']`)
* `loadPluginTemplate($template_name)` - подключение PHP-файла шаблона `$template_name` из поддиректории `templates` текущего плагина
* `loadPluginJS($js_name)` - подключение JS-файла `$js_name` из поддиректории `scripts` текущего плагина
* `loadPluginCSS($css_name)` - подключение CSS-файла `$css_name` из поддиректории `styles` текущего плагина
* `getPluginUrl()` - возвращает URL-путь к директории текущего плагина

## Примеры рабочих плагинов

* [Profile Starsigns](https://e.pcloud.link/publink/show?code=XZjmFuZ0SzABxCOdluGxcu5nu1Ls49GAybX)
