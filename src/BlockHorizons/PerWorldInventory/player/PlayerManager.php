<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\player;

use BlockHorizons\PerWorldInventory\PerWorldInventory;
use pocketmine\player\Player;

final class PlayerManager{

	/** @var PlayerInstance[] */
	private array $players = [];

	public function __construct(PerWorldInventory $plugin){
		$plugin->getServer()->getPluginManager()->registerEvents(new PlayerListener($this), $plugin);
	}

	public function onPlayerJoin(Player $player) : void{
		$this->players[$player->getId()] = new PlayerInstance();
	}

	public function onPlayerQuit(Player $player) : void{
		unset($this->players[$player->getId()]);
	}

	public function get(Player $player) : PlayerInstance{
		return $this->players[$player->getId()];
	}
}