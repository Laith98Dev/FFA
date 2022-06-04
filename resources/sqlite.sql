-- #!sqlite
-- #{ffa
-- #  {init
CREATE TABLE IF NOT EXISTS players ( 
  player VARCHAR NOT NULL PRIMARY KEY,
  kills INT NOT NULL,
  deaths INT NOT NULL
);
-- # &
CREATE TABLE IF NOT EXISTS arenas ( 
  name VARCHAR NOT NULL PRIMARY KEY,
  world VARCHAR NOT NULL,
  lobby VARCHAR NOT NULL,
  respawn VARCHAR NOT NULL
);
-- #  }
-- #  {get-arena
-- #     :name string
SELECT * FROM arenas WHERE name = :name;
-- #  }
-- #  {get-arenas
SELECT * FROM arenas;
-- #  }
-- #  {add-arena
-- #     :name string
-- #     :world string
-- #     :lobby string
-- #     :respawn string
INSERT INTO arenas(name, world, lobby, respawn) VALUES ( :name , :world, :lobby, :respawn);
-- #  }
-- #  {delete-arena
-- #     :name string
DELETE FROM arenas WHERE name = :name;
-- #  }
-- #  {update-lobby
-- #     :name string
-- #     :lobby string
UPDATE arenas SET lobby = :lobby WHERE name = :name;
-- #  }
-- #  {update-respawn
-- #     :name string
-- #     :respawn string
UPDATE arenas SET respawn = :respawn WHERE name = :name;
-- #  }
-- #  {add-kills
-- #     :player string
-- #     :kills int
INSERT INTO players(player, kills, deaths) VALUES ( :player , 1, 0 )
    ON CONFLICT(player) DO UPDATE SET kills = kills + :kills WHERE player = :player;
-- #  }
-- #  {add-deaths
-- #     :player string
-- #     :deaths int
INSERT INTO players(player, kills, deaths) VALUES ( :player , 0, 1 )
    ON CONFLICT(player) DO UPDATE SET deaths = deaths + :deaths WHERE player = :player;
-- #  }
-- #  {get-kills
-- #     :player string
SELECT kills FROM players WHERE player = :player;
-- #  }
-- #  {get-deaths
-- #     :player string
SELECT deaths FROM players WHERE player = :player;
-- #  }
-- #}