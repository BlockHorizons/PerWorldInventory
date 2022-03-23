<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\player;

use BlockHorizons\PerWorldInventory\world\WorldInstance;

final class PlayerInstance{

	/** @var int[] */
	private array $waiting = [];

	public function wait(WorldInstance $instance) : void{
		$this->waiting[spl_object_id($instance)] = time();
	}

	public function notify(WorldInstance $instance) : void{
		unset($this->waiting[spl_object_id($instance)]);
	}

	public function isWaiting() : bool{
		return count($this->waiting) > 0;
	}
}