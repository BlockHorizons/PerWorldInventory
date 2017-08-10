<?php

declare(strict_types = 1);

namespace BlockHorizons\PerWorldInventory\listeners;

use BlockHorizons\PerWorldInventory\PerWorldInventory;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\Player;

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

		$this->getPlugin()->storeInventory($player, $event->getOrigin());

		if(in_array($event->getTarget()->getName(), $this->getPlugin()->getConfig()->getNested("Bundled-Worlds." . $event->getOrigin()->getName(), []))) {
			return;
		} elseif(in_array($event->getOrigin()->getName(), $this->getPlugin()->getConfig()->getNested("Bundled-Worlds." . $event->getTarget()->getName(), []))) {
			return;
		}

		if($player->hasPermission("per-world-inventory.bypass")) {
			return;
		}
		$player->getInventory()->clearAll();
		$player->getInventory()->setContents($this->getPlugin()->fetchInventory($player, $event->getTarget()));
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
		$valid = false;
		foreach($event->getPlayer()->getInventory()->getContents() as $content) {
			if($content->getId() !== Item::AIR) {
				$valid = true;
				break;
			}
		}
		if($valid) {
			$this->getPlugin()->storeInventory($event->getPlayer(), $event->getPlayer()->getLevel());
		}
	}

	/**
	 * @param PlayerJoinEvent $event
	 *
	 * @priority HIGH
	 */
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		if($player->isCreative()) {
			return;
		}
		if($player->hasPermission("per-world-inventory.bypass")) {
			return;
		}
		$player->getInventory()->clearAll();
		$player->getInventory()->setContents($this->getPlugin()->fetchInventory($player, $player->getLevel()));
	}
}