<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory;

use BlockHorizons\PerWorldInventory\player\PlayerManager;
use BlockHorizons\PerWorldInventory\world\WorldManager;
use pocketmine\plugin\PluginBase;

final class PerWorldInventory extends PluginBase{

	/** @var PlayerManager */
	private $player_manager;

	/** @var WorldManager */
	private $world_manager;

	public function onEnable() : void{
		$this->player_manager = new PlayerManager($this);
		$this->world_manager = new WorldManager($this);
	}

	public function onDisable() : void{
		$this->world_manager->close();
	}

	/**
	 * @internal
	 * @return PlayerManager
	 */
	public function getPlayerManager() : PlayerManager{
		return $this->player_manager;
	}

	/**
	 * @internal
	 * @return WorldManager
	 */
	public function getWorldManager() : WorldManager{
		return $this->world_manager;
	}
}
