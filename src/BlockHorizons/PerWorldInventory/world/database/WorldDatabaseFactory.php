<?php

declare(strict_types = 1);

namespace BlockHorizons\PerWorldInventory\world\database;

use BlockHorizons\PerWorldInventory\PerWorldInventory;

final class WorldDatabaseFactory{

	public static function create(PerWorldInventory $plugin) : WorldDatabase{
		switch(strtolower($type = $plugin->getConfig()->getNested("database.type"))){
			case "mysql":
				return new MySQLWorldDatabase($plugin);
			case "sqlite":
				return new SQLiteWorldDatabase($plugin);
			default:
				throw new \InvalidArgumentException("Invalid database type " . $type);
		}
	}
}