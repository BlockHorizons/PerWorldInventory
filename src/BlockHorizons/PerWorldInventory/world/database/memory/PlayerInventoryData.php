<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\world\database\memory;

use pocketmine\item\Item;
use pocketmine\Player;

final class PlayerInventoryData{

	/** @var Item[] */
	private $armor;

	/** @var Item[] */
	private $inventory;

	public function __construct(Player $player){
		$this->armor = $player->getArmorInventory()->getContents();
		$this->inventory = $player->getInventory()->getContents();
	}

	/**
	 * @return Item[]
	 */
	public function getArmorContents() : array{
		return $this->armor;
	}

	/**
	 * @return Item[]
	 */
	public function getInventoryContents() : array{
		return $this->inventory;
	}
}