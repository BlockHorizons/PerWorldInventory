<?php

declare(strict_types = 1);

namespace BlockHorizons\PerWorldInventory;

use BlockHorizons\PerWorldInventory\listeners\EventListener;
use BlockHorizons\PerWorldInventory\tasks\LoadInventoryTask;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\FileWriteTask;

class PerWorldInventory extends PluginBase {

	/** @var string */
	private $base_directory;

	/** @var Item[][] */
	private $loaded_inventories = [];

	/** @var bool[] */
	private $loading = [];

	public function onEnable() : void {
		if(!is_dir($this->getDataFolder())) {
			mkdir($this->getDataFolder());
			$this->saveDefaultConfig();
			mkdir($this->getDataFolder() . "inventories");
		}

		$this->base_directory = $this->getDataFolder() . "inventories/";
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}

	public function onDisable() : void {
		$this->saveAllInventories();
	}

	public function updateOldFiles() : \Generator {
		$reader = new BigEndianNBTStream();

		foreach(scandir($this->base_directory) as $file) {
			if(substr($file, -4) === ".yml") {
				$player = substr($file, 0, -4);
				$contents = yaml_parse_file($this->base_directory . $file);

				foreach($contents as $level => $b64inventory) {
					$items = [];
					foreach($reader->readCompressed(base64_decode($b64inventory))->getListTag("ItemList") as $item_tag) {
						$items[] = Item::nbtDeserialize($item_tag);
					}

					$this->loaded_inventories[$player][$level] = $items;
				}

				$this->save($player, true);
				yield $file;
			}
		}
	}

	public function getInventory(Player $player, Level $level) : array {
		return $this->loaded_inventories[$player->getLowerCaseName()][$level->getFolderName()] ?? [];
	}

	public function storeInventory(Player $player, Level $level) : void {
		$contents = $player->getInventory()->getContents();
		$armorInventory = $player->getArmorInventory();

		for($slot = 100; $slot < 104; ++$slot) {
			$item = $armorInventory->getItem($slot - 100);
			if(!$item->isNull()) {
				$contents[$slot] = $item;
			}
		}

		if(empty($contents)) {
			unset($this->loaded_inventories[$player->getLowerCaseName()][$level->getFolderName()]);
		}else{
			$this->loaded_inventories[$player->getLowerCaseName()][$level->getFolderName()] = $contents;
		}
	}

	public function load(Player $player) : void {
		$filepath = $this->base_directory . $player->getLowerCaseName() . ".dat";
		if(file_exists($filepath)) {
			$this->getServer()->getScheduler()->scheduleAsyncTask(new LoadInventoryTask($player, $filepath));
			$this->loading[$player->getLowerCaseName()] = true;
		}
	}

	public function isLoading(Player $player) : bool {
		return isset($this->loading[$player->getLowerCaseName()]);
	}

	public function onAbortLoading(string $playername) : void {
		unset($this->loading[$playername]);
	}

	public function onLoadInventory(Player $player, array $contents) : void {
		unset($this->loading[$player->getLowerCaseName()]);
		$level = $player->getLevel()->getFolderName();

		if(isset($contents[$level])) {
			$armorInventory = $player->getArmorInventory();
			$armorInventory->clearAll(false);

			$inventory = $player->getInventory();
			$inventory->clearAll(false);

			foreach($contents[$level] as $slot => $item) {
				($slot >= 100 && $slot < 104 ? $armorInventory : $inventory)->setItem($slot, $item, false);
			}

			$armorInventory->sendContents($player);
			$inventory->sendContents($player);
			unset($contents[$level]);
		}

		$this->loaded_inventories[$player->getLowerCaseName()] = $contents;
	}

	public function save($player, bool $unset = false) : void {
		$key = $player instanceof Player ? $player->getLowerCaseName() : strtolower($player);
		if(!empty($this->loaded_inventories[$key])) {
			$tag = new CompoundTag();
			foreach($this->loaded_inventories[$key] as $level_name => $contents) {
				$inventory = new ListTag($level_name);
				foreach($contents as $slot => $item) {
					$inventory->push($item->nbtSerialize($slot));
				}
				$tag->setTag($inventory);
			}

			$file_path = $this->base_directory . $key . ".dat";
			$compressedFileContents = (new BigEndianNBTStream())->writeCompressed($tag);

			$this->getServer()->getScheduler()->scheduleAsyncTask(new FileWriteTask($file_path, $compressedFileContents));
		} elseif (file_exists($this->base_directory . $key . ".dat")) {
			unlink($this->base_directory . $key . ".dat");
		}

		if($unset) {
			unset($this->loaded_inventories[$key]);
		}
	}

	public function saveAllInventories() : void {
		foreach(array_keys($this->loaded_inventories) as $player) {
			$this->save($player);
		}
	}

	public function onCommand(CommandSender $issuer, Command $cmd, $label, array $args) : bool {
		if(isset($args[0])) {
			switch($args[0]) {
				case "updateoldfiles":
					$issuer->sendMessage("Updating old files...");

					$i = 0;
					foreach($this->updateOldFiles() as $file) {
						$issuer->sendMessage("Updated $file");
						$i++;
					}

					$issuer->sendMessage("Update finished. Updated ($i) files.");
					break;
			}
		}

		return true;
	}
}