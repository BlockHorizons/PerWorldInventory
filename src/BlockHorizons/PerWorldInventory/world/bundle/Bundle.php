<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\world\bundle;

final class Bundle{

	/** @var array<string, bool> */
	private array $worlds = [];

	public function add(string $world) : void{
		$this->worlds[$world] = true;
	}

	public function remove(string $world) : void{
		unset($this->world[$world]);
	}

	public function contains(string $world) : bool{
		return isset($this->worlds[$world]);
	}
}