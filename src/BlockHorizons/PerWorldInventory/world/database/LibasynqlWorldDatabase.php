<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\world\database;

use BlockHorizons\PerWorldInventory\PerWorldInventory;
use BlockHorizons\PerWorldInventory\world\WorldInstance;
use Closure;
use pocketmine\player\Player;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

abstract class LibasynqlWorldDatabase implements WorldDatabase{

	private DataConnector $database;

	public function __construct(PerWorldInventory $plugin){
		$this->database = libasynql::create($plugin, $plugin->getConfig()->get("database"), ["sqlite" => "db/sqlite.sql", "mysql" => "db/mysql.sql"]);

		$this->database->executeGeneric(WorldDatabaseStmts::INIT_UNBUNDLED_INVENTORIES);
		$this->database->executeGeneric(WorldDatabaseStmts::INIT_BUNDLED_INVENTORIES);
		$this->database->waitAll();
	}

	public function load(WorldInstance $world, Player $player, Closure $onLoad) : void{
		$params = ["player" => strtolower($player->getName())];

		$bundle = $world->getBundle();
		if($bundle !== null){
			$stmt = WorldDatabaseStmts::LOAD_BUNDLED;
			$params["bundle"] = $bundle;
		}else{
			$stmt = WorldDatabaseStmts::LOAD_UNBUNDLED;
			$params["world"] = $world->getName();
		}

		$this->database->executeSelect($stmt, $params, function(array $rows) use ($onLoad) : void{
			if(isset($rows[0])){
				["armor_inventory" => $armor, "inventory" => $inventory] = $rows[0];
				$onLoad(WorldDatabaseUtils::unserializeInventoryContents($this->fetchBinaryString($armor)), WorldDatabaseUtils::unserializeInventoryContents($this->fetchBinaryString($inventory)));
			}else{
				$onLoad([], []);
			}
		});
	}

	public function save(WorldInstance $world, Player $player, bool $quit) : void{
		$params = ["player" => strtolower($player->getName()), "armor_inventory" => $this->saveBinaryString(WorldDatabaseUtils::serializeInventoryContents($player->getArmorInventory()->getContents())), "inventory" => $this->saveBinaryString(WorldDatabaseUtils::serializeInventoryContents($player->getInventory()->getContents()))];

		$bundle = $world->getBundle();
		if($bundle !== null){
			$stmt = WorldDatabaseStmts::SAVE_BUNDLED;
			$params["bundle"] = $bundle;
		}else{
			$stmt = WorldDatabaseStmts::SAVE_UNBUNDLED;
			$params["world"] = $world->getName();
		}

		$this->database->executeInsert($stmt, $params);
	}

	abstract protected function fetchBinaryString(string $string) : string;

	abstract protected function saveBinaryString(string $string) : string;

	public function close() : void{
		$this->database->waitAll();
		$this->database->close();
	}
}