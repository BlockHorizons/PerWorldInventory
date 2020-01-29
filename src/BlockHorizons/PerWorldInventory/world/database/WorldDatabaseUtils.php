<?php

declare(strict_types = 1);

namespace BlockHorizons\PerWorldInventory\world\database;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

final class WorldDatabaseUtils{

	private const TAG_CONTENTS = "Contents";

	private static function getSerializer() : BigEndianNBTStream{
		static $serializer = null;
		return $serializer ?? $serializer = new BigEndianNBTStream();
	}

	/**
	 * Serializes inventory contents, along with their key value relation.
	 *
	 * @param array<int, Item> $contents
	 * @return string
	 */
	public static function serializeInventoryContents(array $contents) : string{
		$tag = new ListTag(self::TAG_CONTENTS, [], NBT::TAG_Compound);
		/**
		 * @var int $slot
		 * @var Item $item
		 */
		foreach($contents as $slot => $item){
			$tag->push($item->nbtSerialize($slot));
		}
		$nbt = new CompoundTag();
		$nbt->setTag($tag);
		return self::getSerializer()->writeCompressed($nbt);
	}

	/**
	 * Unserializes serialized inventory contents, maintaining their key
	 * value relation.
	 *
	 * @param string $serialized
	 * @return array<int, Item>
	 */
	public static function unserializeInventoryContents(string $serialized) : array{
		$nbt = self::getSerializer()->readCompressed($serialized);
		assert($nbt instanceof CompoundTag);

		$contents = [];

		/** @var CompoundTag $entry */
		foreach($nbt->getListTag(self::TAG_CONTENTS) as $entry){
			$contents[$entry->getByte("Slot")] = Item::nbtDeserialize($entry);
		}

		return $contents;
	}
}