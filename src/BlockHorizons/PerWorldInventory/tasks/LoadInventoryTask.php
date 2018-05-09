<?php

declare(strict_types = 1);

namespace BlockHorizons\PerWorldInventory\tasks;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class LoadInventoryTask extends AsyncTask {

	/** @var string */
	private $playerRawUUID;

	/** @var string */
	private $playername;

	/** @var string */
	private $filepath;

	public function __construct(Player $player, string $filepath) {
		$this->playerRawUUID = $player->getRawUniqueId();
		$this->playername = $player->getLowerCaseName();
		$this->filepath = $filepath;
	}

	public function onRun() : void {
		$raw_contents = file_get_contents($this->filepath);
		$tag = (new BigEndianNBTStream())->readCompressed($raw_contents);
		$result = [];

		foreach($tag->getValue() as $level_name => $inventory_tag) {
			$contents = [];
			foreach($inventory_tag as $item_tag) {
				$contents[$item_tag->getByte("Slot")] = Item::nbtDeserialize($item_tag);
			}
			$result[$level_name] = $contents;
		}

		$this->setResult($result);
	}

	public function onCompletion(Server $server) : void {
		$player = $server->getPlayerByRawUUID($this->playerRawUUID);
		$plugin = $server->getPluginManager()->getPlugin("PerWorldInventory");

		if($player === null) {
			$plugin->onAbortLoading($this->playername);
			return;
		}

		$plugin->onLoadInventory($player, $this->getResult());
	}
}