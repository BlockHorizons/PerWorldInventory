-- #!sqlite
-- #{ perworldinventory

-- #  { init
-- #    { unbundled
CREATE TABLE IF NOT EXISTS inventories
(
    world           VARCHAR(64) NOT NULL,
    player          VARCHAR(16) NOT NULL,
    armor_inventory BLOB        NOT NULL,
    inventory       BLOB        NOT NULL,
    PRIMARY KEY (world, player)
);
-- #    }
-- #    { bundled
CREATE TABLE IF NOT EXISTS bundled_inventories
(
    bundle          VARCHAR(64) NOT NULL,
    player          VARCHAR(16) NOT NULL,
    armor_inventory BLOB        NOT NULL,
    inventory       BLOB        NOT NULL,
    PRIMARY KEY (bundle, player)
);
-- #    }
-- #  }

-- #  { load
-- #    { unbundled
-- #      :world string
-- #      :player string
SELECT HEX(armor_inventory) as armor_inventory, HEX(inventory) AS inventory
FROM inventories
WHERE world = :world
  AND player = :player;
-- #    }
-- #    { bundled
-- #      :bundle string
-- #      :player string
SELECT HEX(armor_inventory) as armor_inventory, HEX(inventory) AS inventory
FROM bundled_inventories
WHERE bundle = :bundle
  AND player = :player;
-- #    }
-- #  }

-- #  { save
-- #    { unbundled
-- #      :world string
-- #      :player string
-- #      :armor_inventory string
-- #      :inventory string
INSERT OR
REPLACE INTO inventories(world, player, armor_inventory, inventory)
VALUES (:world, :player, X:armor_inventory, X:inventory);
-- #    }
-- #    { bundled
-- #      :bundle string
-- #      :player string
-- #      :armor_inventory string
-- #      :inventory string
INSERT OR
REPLACE INTO bundled_inventories(bundle, player, armor_inventory, inventory)
VALUES (:bundle, :player, X:armor_inventory, X:inventory);
-- #    }
-- #  }

-- #}