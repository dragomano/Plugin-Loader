# Plugin Loader

[![SMF 2.1](https://img.shields.io/badge/SMF-2.1-ed6033.svg?style=flat)](https://github.com/SimpleMachines/SMF2.1)
![License](https://img.shields.io/github/license/dragomano/plugin-loader)
![Hooks only: Yes](https://img.shields.io/badge/Hooks%20only-YES-blue)
![PHP](https://img.shields.io/badge/PHP-^8.0-blue.svg?style=flat)
[![Crowdin](https://badges.crowdin.net/plugin-loader/localized.svg)](https://crowdin.com/project/plugin-loader)

[Description in English](README.md)

Этот концепт-мод вдохновлён системой плагинов Wedge. Он добавляет поддержку плагинов в SMF.

Плагины — это автономные модификации, которым не требуется установка или удаление через Менеджер пакетов. Они не вносят изменения в файлы SMF и работают полностью на хуках.

Точкой входа каждого плагина является **plugin.php** с анонимным классом внутри. Также в директории каждого плагина должен находиться файл **plugin-info.xml**, содержащий ключевые данные плагина:

    * название
    * описание
    * версия плагина
    * имя автора
    * ссылка на сайт автора (не обязательно)
    * имейл автора (не обязательно)
    * используемая лицензия
    * ссылка на сайт плагина

Плагины включаются и выключаются одним нажатием кнопки. Для установки достаточно поместить папку плагина с правильной структурой в директорию **Plugins**.

![](preview.png)

Список текущих активных плагинов форума хранится в глобальной переменной **$plugins** в файле _Settings.php_. Для отключения проблемного плагина достаточно _удалить его название из переменной $plugins_, либо _переименовать папку плагина_, либо _переименовать файл plugin.php_ плагина.

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
		plugin.php
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
	<settings>
		<setting name="key1" type="text" default="" />
		<setting name="key2" type="large_text" default="" />
		<setting name="key3" type="check" default="1" />
		<setting name="key4" type="int" default="1" />
	</settings>
</plugin>
```

Плагины, требующие для своей работы создание таблиц в базе данных, должны содержать узел `<database>имя_файла.php</database>` в **plugin-info.xml**. В указанном файле можно разместить скрипт создания нужных таблиц при включении плагина, если они ещё не созданы.

## Пример файла plugin.php

```php
<?php

/**
 * plugin.php
 *
 * @package Example
 * @link https://plugin-site.com
 * @author Author https://author-site.com
 * @copyright 2024 Author
 * @license https://opensource.org/licenses/MIT The MIT License
 *
 * @version 0.1
 */

use Bugo\PluginLoader\Hook;
use Bugo\PluginLoader\Plugin;

if (! defined('SMF'))
	die('No direct access...');

return class extends Plugin
{
	public const NAME = 'example';

	#[Hook('integrate_load_theme', self::class . '::loadTheme#', __FILE__)]
	public function loadTheme(): void
	{
		// Ваш код

		// Используем языковые строчки
		// $this->loadLanguage();
		// var_dump($this->txt['key'])

		// Используем шаблон
		// $this->loadTemplate('Example'); // будет загружен /templates/Example.template.php

		// Используем другой source-файл того же плагина
		// $this->loadSource('other); // будет загружен /sources/other.php

		// Используем CSS-файл
		// $this->loadCSS('test'); // будет загружен /styles/test.css

		// Используем JS-файл
		// $this->loadJS('test'); // будет загружен /scripts/test.js

		// Используем настройки плагина
		// var_dump($this->getSettings());
	}

	#[Hook('integrate_menu_buttons', self::class . '::menuButtons#', __FILE__)]
	public function menuButtons($buttons): void
	{
		// var_dump($buttons);
	}
};

```

Как видите, все требуемые плагином хуки определяются с помощью атрибута `Hook`.

## Пример языкового файла плагина

```php
<?php

return [
	'key1' => 'Текст 1',
	'key2' => 'Текст 2',
];

```

## Вспомогательные методы

Для работы внутри классов плагинов предусмотрены следующие методы:

- `loadLanguage($lang_name = '')` - подключение языкового PHP-файла `$lang_name` из поддиректории `languages` текущего плагина (по умолчанию `$lang_name = $context['user']['language']`)
- `loadTemplate($template_name)` - подключение PHP-файла шаблона `$template_name` из поддиректории `templates` текущего плагина
- `loadCSS($css_name)` - подключение CSS-файла `$css_name` из поддиректории `styles` текущего плагина
- `loadJS($js_name)` - подключение JS-файла `$js_name` из поддиректории `scripts` текущего плагина
- `loadSource($source_name)` - подключение PHP-файла `$source_name` из поддиректории `sources` текущего плагина
- `getUrl($sub_directory = '')` - возвращает URL-путь к директории текущего плагина, включая `$sub_directory` (если указана)

## Примеры рабочих плагинов

- [Profile Starsigns](https://drive.proton.me/urls/8ZX5G1QXSR#WG0Yl99C0NJw)
- [Font Awesome](https://drive.proton.me/urls/ABF7BBDC80#Eo0cVWRbrbxi)
- [Yandex Metrica](https://drive.proton.me/urls/16ZEE2PCKW#UI0yxQoG7BKP)
