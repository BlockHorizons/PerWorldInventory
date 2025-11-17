<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\world\database;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

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
		$list = new ListTag(); 

        foreach($contents as $slot => $item){
            $entry = $item->nbtSerialize();
            $entry->setByte("Slot", $slot);

            $list->push($entry);
        }
        $nbt = CompoundTag::create()->setTag(self::TAG_CONTENTS, $list);

        return self::getSerializer()->write(new TreeRoot($nbt));
	}

	/**
	 * Unserializes serialized inventory contents, maintaining their key
	 * value relation.
	 *
	 * @param string $serialized
	 * @return array<int, Item>
	 */
	public static function unserializeInventoryContents(string $serialized) : array{
        /** @var CompoundTag $root */
        $root = self::getSerializer()->read($serialized)->getTag();
        $contents = [];
        $list = $root->getListTag(self::TAG_CONTENTS);

        if($list !== null){
            foreach($list as $entry){
                if(!$entry instanceof CompoundTag){
                    continue;
                }
                $slot = $entry->getByte("Slot");
                $contents[$slot] = Item::nbtDeserialize($entry);
            }
        }
        return $contents;
    }
}