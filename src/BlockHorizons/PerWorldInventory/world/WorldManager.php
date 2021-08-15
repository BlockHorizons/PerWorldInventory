<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\world;

use BlockHorizons\PerWorldInventory\PerWorldInventory;
use BlockHorizons\PerWorldInventory\player\PlayerManager;
use BlockHorizons\PerWorldInventory\world\bundle\BundleManager;
use BlockHorizons\PerWorldInventory\world\database\WorldDatabase;
use BlockHorizons\PerWorldInventory\world\database\WorldDatabaseFactory;
use pocketmine\level\Level;
use pocketmine\Server;

final class WorldManager{

	/** @var BundleManager */
	private $bundle;

	/** @var WorldDatabase */
	private $database;

	/** @var PlayerManager */
	private $player_manager;

	/** @var WorldInstance[] */
	private $worlds = [];

	public function __construct(PerWorldInventory $plugin){
		$this->bundle = new BundleManager($plugin->getConfig()->get("Bundled-Worlds"));
		$this->database = WorldDatabaseFactory::create($plugin);
		$this->player_manager = $plugin->getPlayerManager();
		$plugin->getServer()->getPluginManager()->registerEvents(new WorldListener($this), $plugin);
	}

	public function close() : void{
		foreach(Server::getInstance()->getLevels() as $world){
			$instance = $this->get($world);
			foreach($world->getPlayers() as $player){
				$instance->save($player);
			}
		}

		$this->database->close();
	}

	public function onWorldLoad(Level $world) : void{
		$this->worlds[$world->getId()] = new WorldInstance($world, $this->database, $this->player_manager, $this->bundle->getBundle($world->getFolderName()));
	}

	public function onWorldUnload(Level $world) : void{
		unset($this->worlds[$world->getId()]);
	}

	public function get(Level $world) : WorldInstance{
		return $this->worlds[$world->getId()];
	}
}