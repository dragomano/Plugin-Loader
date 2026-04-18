<?php

declare(strict_types=1);

require_once __DIR__ . '/SmfTestState.php';

use Tests\Support\SmfTestState;

function add_integration_function(string $hook, string $callback, bool $permanent, string $file): void
{
	SmfTestState::record('hooks', [
		'hook'      => $hook,
		'callback'  => $callback,
		'permanent' => $permanent,
		'file'      => $file,
	]);
}

function log_error($error_message, $error_type = 'general', $file = null, $line = null)
{
	SmfTestState::record('logged_errors', [
		'message' => $error_message,
		'type'    => $error_type,
		'file'    => $file,
		'line'    => $line,
	]);

	return $error_message;
}

function loadLanguage(string $name): void
{
	SmfTestState::record('loadLanguage', $name);
}

function loadCSSFile(string $name, array $options = []): void
{
	SmfTestState::record('loadCSSFile', [$name, $options]);
}

function loadJavaScriptFile(string $name, array $options = []): void
{
	SmfTestState::record('loadJavaScriptFile', [$name, $options]);
}

function loadTemplate(string $name): void
{
	SmfTestState::record('loadTemplate', $name);
}

function checkSession(string $type = 'post', string $from = 'admin', bool $isFatal = true): void
{
	SmfTestState::record('checkSession', [
		'type'    => $type,
		'from'    => $from,
		'isFatal' => $isFatal,
	]);
}

function smf_json_decode(string $json, bool $associative = false): mixed
{
	if (array_key_exists('smf_json_decode_override', $GLOBALS)) {
		return $GLOBALS['smf_json_decode_override'];
	}

	return json_decode($json, $associative);
}

function redirectexit(string $location): never
{
	throw new RuntimeException('redirect:' . $location);
}

function db_extend(string $area): void
{
	SmfTestState::record('db_extend', $area);
}

function updateSettingsFile(array $settings): void
{
	SmfTestState::record('updateSettingsFile', $settings);
}

function call_helper(callable $callable): mixed
{
	return $callable();
}

function loadGeneralSettingParameters(array $subActions, string $default): void
{
	if (
		! isset($GLOBALS['context']['sub_action'])
		|| ! isset($subActions[$GLOBALS['context']['sub_action']])
	) {
		$GLOBALS['context']['sub_action'] = $default;
	}
}

function memoryReturnBytes(string $value): int
{
	$value = trim($value);
	$unit  = strtolower(substr($value, -1));
	$bytes = (int) $value;

	return match ($unit) {
		'g'     => $bytes * 1024 * 1024 * 1024,
		'm'     => $bytes * 1024 * 1024,
		'k'     => $bytes * 1024,
		default => $bytes,
	};
}
