<?php

declare(strict_types=1);

namespace BlockHorizons\PerWorldInventory\world\database;

interface WorldDatabaseStmts{

	public const INIT_UNBUNDLED_INVENTORIES = "perworldinventory.init.unbundled";
	public const INIT_BUNDLED_INVENTORIES = "perworldinventory.init.bundled";

	public const LOAD_UNBUNDLED = "perworldinventory.load.unbundled";
	public const LOAD_BUNDLED = "perworldinventory.load.bundled";

	public const SAVE_UNBUNDLED = "perworldinventory.save.unbundled";
	public const SAVE_BUNDLED = "perworldinventory.save.bundled";
}