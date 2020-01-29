<?php

declare(strict_types = 1);

namespace BlockHorizons\PerWorldInventory\world\database;

final class MySQLWorldDatabase extends WorldDatabase{

	protected function fetchBinaryString(string $string) : string{
		return $string;
	}

	protected function saveBinaryString(string $string) : string{
		return $string;
	}
}