<?php

declare(strict_types=1);

namespace Tests\Support;

final class SmfTestState
{
	private static array $records = [];

	public static function reset(): void
	{
		self::$records = [];
	}

	public static function record(string $key, mixed $value): void
	{
		self::$records[$key] ??= [];
		self::$records[$key][] = $value;
	}

	public static function all(string $key): array
	{
		return self::$records[$key] ?? [];
	}

	public static function last(string $key): mixed
	{
		$values = self::all($key);

		return $values === [] ? null : $values[array_key_last($values)];
	}
}
