<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\world\database;

use BlockHorizons\PerWorldInventory\PerWorldInventory;
use BlockHorizons\PerWorldInventory\world\WorldInstance;
use Closure;
use pocketmine\player\Player;
use SQLite3;

final class MemoryWorldDatabase implements WorldDatabase{

	private bool $delete_on_quit;

	private SQLite3 $database;

	public function __construct(PerWorldInventory $plugin){
		$this->delete_on_quit = $plugin->getConfig()->getNested("database.memory.delete-on-quit");

		$this->database = new SQLite3(":memory:");
		$this->database->exec("
			PRAGMA locking_mode=EXCLUSIVE;
			PRAGMA synchronous=OFF;
			PRAGMA temp_store=MEMORY;
			PRAGMA journal_mode=OFF;
			PRAGMA cache_size=10000;
			PRAGMA page_size=65535;

			CREATE TABLE IF NOT EXISTS inventories
			(
			    world           VARCHAR(64) NOT NULL,
			    player          VARCHAR(16) NOT NULL,
			    armor_inventory BLOB        NOT NULL,
			    inventory       BLOB        NOT NULL,
			    PRIMARY KEY (world, player)
			);

			CREATE TABLE IF NOT EXISTS bundled_inventories
			(
			    bundle          VARCHAR(64) NOT NULL,
			    player          VARCHAR(16) NOT NULL,
			    armor_inventory BLOB        NOT NULL,
			    inventory       BLOB        NOT NULL,
			    PRIMARY KEY (bundle, player)
			);
		");
	}

	public function load(WorldInstance $world, Player $player, Closure $onLoad) : void{
		$bundle = $world->getBundle();
		if($bundle !== null){
			$stmt = $this->database->prepare("SELECT HEX(armor_inventory) as armor_inventory, HEX(inventory) AS inventory FROM bundled_inventories WHERE bundle = :bundle AND player = :player");
			$stmt->bindParam(":bundle", $bundle);
		}else{
			$name = $world->getName();
			$stmt = $this->database->prepare("SELECT HEX(armor_inventory) as armor_inventory, HEX(inventory) AS inventory FROM inventories WHERE world = :world AND player = :player");
			$stmt->bindParam(":world", $name);
		}

		$player_name = strtolower($player->getName());
		$stmt->bindParam(":player", $player_name);
		$result = $stmt->execute();
		$invs = $result->fetchArray(SQLITE3_ASSOC);
		$result->finalize();
		$stmt->close();

		$onLoad(
			isset($invs["armor_inventory"]) ? WorldDatabaseUtils::unserializeInventoryContents(hex2bin($invs["armor_inventory"])) : [],
			isset($invs["inventory"]) ? WorldDatabaseUtils::unserializeInventoryContents(hex2bin($invs["inventory"])) : []
		);
	}

	public function save(WorldInstance $world, Player $player, bool $quit) : void{
		if($quit && $this->delete_on_quit){
			$stmt = $this->database->prepare("DELETE FROM inventories WHERE player=:player");
			$stmt->bindValue(":player", strtolower($player->getName()));
			$stmt->execute();
			$stmt->close();

			$stmt = $this->database->prepare("DELETE FROM bundled_inventories WHERE player=:player");
			$stmt->bindValue(":player", strtolower($player->getName()));
			$stmt->execute();
			$stmt->close();
		}else{
			$bundle = $world->getBundle();
			if($bundle !== null){
				$stmt = $this->database->prepare("INSERT OR REPLACE INTO bundled_inventories(bundle, player, armor_inventory, inventory) VALUES (:bundle, :player, :armor_inventory, :inventory)");
				$stmt->bindValue(":bundle", $bundle);
			}else{
				$stmt = $this->database->prepare("INSERT OR REPLACE INTO inventories(world, player, armor_inventory, inventory) VALUES (:world, :player, :armor_inventory, :inventory)");
				$stmt->bindValue(":world", $world->getName());
			}

			$stmt->bindValue(":player", strtolower($player->getName()));
			$stmt->bindValue(":armor_inventory", WorldDatabaseUtils::serializeInventoryContents($player->getArmorInventory()->getContents()));
			$stmt->bindValue(":inventory", WorldDatabaseUtils::serializeInventoryContents($player->getInventory()->getContents()));
			$stmt->execute();
			$stmt->close();
		}
	}

	public function close() : void{
		$this->database->close();
	}
}