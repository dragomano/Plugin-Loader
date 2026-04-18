<?php

declare(strict_types=1);

namespace MatthiasMullie\Minify;

final class CSS
{
	private string $source = '';

	public function add(string $source): void
	{
		$this->source = $source;
	}

	public function minify(string $target): void
	{
		file_put_contents($target, (string) file_get_contents($this->source));
	}
}

final class JS
{
	private string $source = '';

	public function add(string $source): void
	{
		$this->source = $source;
	}

	public function minify(string $target): void
	{
		file_put_contents($target, (string) file_get_contents($this->source));
	}
}
