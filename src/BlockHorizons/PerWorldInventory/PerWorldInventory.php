<?php

declare(strict_types = 1);

namespace BlockHorizons\PerWorldInventory;

use BlockHorizons\PerWorldInventory\listeners\EventListener;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use spoondetector\SpoonDetector;

class PerWorldInventory extends PluginBase {

	public function onEnable() {
		if(!is_dir($this->getDataFolder())) {
			mkdir($this->getDataFolder());
			SpoonDetector::printSpoon($this);
			$this->saveDefaultConfig();
			mkdir($this->getDataFolder() . "inventories");
		}
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}

	/**
	 * @param Player $player
	 *
	 * @return string
	 */
	public function compressInventoryContents(Player $player): string {
		$items = $player->getInventory()->getContents();
		foreach($items as &$item) {
			$item = $item->nbtSerialize(-1, "Item");
		}
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$compressedContents = new CompoundTag("Items", [
			new ListTag("ItemList", $items)
		]);
		$nbt->setData($compressedContents);
		return base64_encode($nbt->writeCompressed(ZLIB_ENCODING_DEFLATE));
	}

	/**
	 * @param Player $player
	 * @param Level  $level
	 * @param string $compressedData
	 *
	 * @return string[]
	 */
	public function putDataFormatted(Player $player, Level $level, string $compressedData): array {
		$file = $this->getDataFolder() . "inventories/" . $player->getLowerCaseName() . ".yml";
		$processedData = [
			$level->getName() => $compressedData
		];
		if(!file_exists($file)) {
			yaml_emit_file($file, $processedData);
		} else {
			$previousData = yaml_parse_file($file);
			$previousData[$level->getName()] = $compressedData;
		}
		return $processedData;
	}

	/**
	 * @param string $compressedData
	 *
	 * @return Item[]
	 */
	public function decompressInventoryContents(string $compressedData): array {
		if(empty($compressedData)) {
			return [];
		}
		$compressedContents = base64_decode($compressedData);
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->readCompressed($compressedContents);
		$nbt = $nbt->getData();

		/** @var ListTag $items */
		$items = $nbt->ItemList ?? [];
		$contents = [];
		if(!empty($items)) {
			$items = $items->getValue();
			foreach($items as $slot => $compoundTag) {
				$contents[$slot] = Item::nbtDeserialize($compoundTag);
			}
		}
		return $contents;
	}

	/**
	 * @param Player $player
	 * @param Level  $level
	 *
	 * @return string[]
	 */
	public function getFromFormattedData(Player $player, Level $level): array {
		$file = $this->getDataFolder() . "inventories/" . $player->getLowerCaseName() . ".yml";
		if(!file_exists($file)) {
			return [
				$level->getName() => ""
			];
		}
		$data = yaml_parse_file($file);
		if(!isset($data[$level->getname()])) {
			return [
				$level->getName() => ""
			];
		}
		return [
			$level->getName() => $data[$level->getName()]
		];
	}

	/**
	 * @param Player $player
	 * @param Level  $level
	 *
	 * @return string[]
	 */
	public function storeInventory(Player $player, Level $level): array {
		$compressedData = $this->compressInventoryContents($player);
		return $this->putDataFormatted($player, $level, $compressedData);
	}

	/**
	 * @param Player $player
	 * @param Level  $level
	 *
	 * @return Item[]
	 */
	public function fetchInventory(Player $player, Level $level): array {
		$data = $this->getFromFormattedData($player, $level);
		return $this->decompressInventoryContents($data[$level->getName()]);
	}
}