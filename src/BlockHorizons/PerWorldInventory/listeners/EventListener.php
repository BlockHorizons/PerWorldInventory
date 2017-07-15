<?php

declare(strict_types = 1);

namespace BlockHorizons\PerWorldInventory\listeners;

use BlockHorizons\PerWorldInventory\PerWorldInventory;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;

class EventListener implements Listener {

	private $plugin;

	public function __construct(PerWorldInventory $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @return PerWorldInventory
	 */
	public function getPlugin(): PerWorldInventory {
		return $this->plugin;
	}

	/**
	 * @param EntityLevelChangeEvent $event
	 *
	 * @priority HIGHEST
	 */
	public function onLevelChange(EntityLevelChangeEvent $event) {
		$player = $event->getEntity();
		if(!$player instanceof Player) {
			return;
		}
		if($player->isCreative()) {
			return;
		}
		foreach($player->getInventory()->getContents() as $content) {
			if($content->getId() !== Item::AIR) {
				$this->getPlugin()->storeInventory($player, $event->getOrigin());
				break;
			}
		}

		if(!in_array($event->getTarget()->getName(), $this->getPlugin()->getConfig()->getNested("Bundled-Worlds." . $event->getOrigin()->getName(), []))) {
			if(!in_array($event->getOrigin()->getName(), $this->getPlugin()->getConfig()->getNested("Bundled-Worlds." . $event->getTarget()->getName(), []))) {
				$player->getInventory()->setContents($this->getPlugin()->fetchInventory($player, $event->getTarget()));
			}
		}
	}

	/**
	 * @param PlayerQuitEvent $event
	 *
	 * @priority MONITOR
	 */
	public function onQuit(PlayerQuitEvent $event) {
		if($event->getPlayer()->isCreative()) {
			return;
		}
		foreach($event->getPlayer()->getInventory()->getContents() as $content) {
			if($content->getId() !== Item::AIR) {
				$this->getPlugin()->storeInventory($event->getPlayer(), $event->getPlayer()->getLevel());
				return;
			}
		}
	}
}