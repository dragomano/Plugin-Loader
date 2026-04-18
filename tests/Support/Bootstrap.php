<?php

declare(strict_types=1);

namespace Tests\Support;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/SmfTestState.php';
require_once __DIR__ . '/TestEnvironment.php';
require_once __DIR__ . '/SmfNamespaceStubs.php';

defined('SMF') || define('SMF', 'TEST');
defined('PLUGINS_DIR') || define('PLUGINS_DIR', sys_get_temp_dir() . '/plugin-loader-tests/Plugins');
defined('PLUGINS_URL') || define('PLUGINS_URL', 'https://example.test/Plugins');
