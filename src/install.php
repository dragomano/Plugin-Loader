<?php

if (file_exists(__DIR__ . '/SSI.php') && ! defined('SMF')) {
	require_once __DIR__ . '/SSI.php';
} elseif(! defined('SMF')) {
	die('<b>Error:</b> Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');
}

if (version_compare(PHP_VERSION, '8.2', '<')) {
	die('This mod needs PHP 8.2 or greater. You will not be able to install/use this mod, contact your host and ask for a php upgrade.');
}

global $sourcedir, $boarddir;

require_once $sourcedir . '/Subs-Package.php';

mktree($boarddir . '/Plugins', 0777);

@copy($sourcedir . '/index.php', $boarddir . '/Plugins/index.php');
