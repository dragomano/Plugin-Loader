<?php

if (file_exists(__DIR__ . '/SSI.php') && !defined('SMF'))
	require_once __DIR__ . '/SSI.php';
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

global $sourcedir, $boarddir;

require_once $sourcedir . '/Subs-Package.php';

mktree($boarddir . '/Plugins', 0777);

@copy($sourcedir . '/index.php', $boarddir . '/Plugins/index.php');
