<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\world\database;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use function zlib_encode;

final class WorldDatabaseUtils{

	private const TAG_CONTENTS = "Contents";

	private static function getSerializer() : BigEndianNbtSerializer{
		static $serializer = null;
		return $serializer ?? $serializer = new BigEndianNbtSerializer();
	}

	/**
	 * Serializes inventory contents, along with their key value relation.
	 *
	 * @param array<int, Item> $contents
	 * @return string
	 */
	public static function serializeInventoryContents(array $contents) : string{
		$items = [];
		/**
		 * @var int $slot
		 * @var Item $item
		 */
		foreach($contents as $slot => $item){
			$items[] = $item->nbtSerialize($slot);
		}
		$nbt = CompoundTag::create()
            ->setTag(self::TAG_CONTENTS, new ListTag($items, NBT::TAG_Compound));

		return zlib_encode(self::getSerializer()->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP);
	}

	/**
	 * Unserializes serialized inventory contents, maintaining their key
	 * value relation.
	 *
	 * @param string $serialized
	 * @return array<int, Item>
	 */
	public static function unserializeInventoryContents(string $serialized) : array{
        $nbt = self::getSerializer()->read(zlib_decode($serialized))->mustGetCompoundTag()->getListTag(self::TAG_CONTENTS);
		$contents = [];

		/** @var CompoundTag $entry */
		foreach($nbt as $entry){
			$contents[$entry->getByte("Slot")] = Item::nbtDeserialize($entry);
		}

		return $contents;
	}
}