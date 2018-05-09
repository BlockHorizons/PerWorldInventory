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

	/**
	 * Returns player's cached inventory contents.
	 *
	 * @param Player $player
	 *
	 * @return array
	 */
	public function getInventory(Player $player, Level $level) : array {
		return $this->loaded_inventories[$player->getLowerCaseName()][$level->getFolderName()] ?? [];
	}

	/**
	 * Stores player's inventory contents to cache, the cache
	 * will later be written to file when the player quits or
	 * if the plugin disabled (generally during server shutdown).
	 *
	 * @param Player $player
	 * @param Level $level
	 */
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

	/**
	 * Loads the player's inventory file asynchronously. Once
	 * the file contents have been fetched asynchronously,
	 * onLoadInventory() is called.
	 *
	 * @param Player $player
	 */
	public function load(Player $player) : void {
		$filepath = $this->base_directory . $player->getLowerCaseName() . ".dat";
		if(file_exists($filepath)) {
			$this->getServer()->getScheduler()->scheduleAsyncTask(new LoadInventoryTask($player, $filepath));
			$this->loading[$player->getLowerCaseName()] = true;
		}
	}

	/**
	 * @param Player $player
	 *
	 * @return bool whether the AsyncTask is still fetching contents.
	 */
	public function isLoading(Player $player) : bool {
		return isset($this->loading[$player->getLowerCaseName()]);
	}

	/**
	 * Called when the file contents have been fetched
	 * asynchronously but the player has logged out by the
	 * time the contents were fetched.
	 *
	 * @param string $playername
	 */
	public function onAbortLoading(string $playername) : void {
		unset($this->loading[$playername]);
	}

	/**
	 * Called when the file contents have been fetched
	 * asynchronously and the Player hasn't quit during
	 * the task.
	 *
	 * @param Player $player
	 * @param Item[][] $contents
	 */
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

	/**
	 * Writes player's cached inventory contents to file.
	 *
	 * @param Player|string $player
	 * @param bool $unset whether to unset the cache
	 */
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

	/**
	 * Writes all cached inventories to file.
	 */
	public function saveAllInventories() : void {
		foreach(array_keys($this->loaded_inventories) as $player) {
			$this->save($player);
		}
	}

	/**
	 * @param CommandSender $issuer
	 * @param Command $cmd
	 * @param string $label
	 * @param array $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $issuer, Command $cmd, string $label, array $args) : bool {
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