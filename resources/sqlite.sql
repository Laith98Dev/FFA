-- #! sqlite
-- #{ ffa

-- #  { init
CREATE TABLE IF NOT EXISTS players ( 
  player VARCHAR NOT NULL PRIMARY KEY,
  kills INT NOT NULL,
  deaths INT NOT NULL,
  kill_streak INT NOT NULL DEFAULT 0
);
-- # &
CREATE TABLE IF NOT EXISTS arenas ( 
  name VARCHAR NOT NULL PRIMARY KEY,
  world VARCHAR NOT NULL,
  lobby VARCHAR NOT NULL,
  respawn VARCHAR NOT NULL,
  kit VARCHAR NOT NULL DEFAULT 'default'
);
-- #  }

-- #  { add-kit-column
ALTER TABLE arenas ADD COLUMN kit VARCHAR NOT NULL DEFAULT 'default';
-- #  }

-- #  { add-kill-streak-column
ALTER TABLE players ADD COLUMN kill_streak INT NOT NULL DEFAULT 0
-- #  }

-- #  { get-arena
-- #     :name string
SELECT * FROM arenas WHERE name = :name;
-- #  }

-- #  { get-arenas
SELECT * FROM arenas;
-- #  }

-- #  { add-arena
-- #     :name string
-- #     :world string
-- #     :lobby string
-- #     :respawn string
-- #     :kit string
INSERT INTO arenas(name, world, lobby, respawn,kit) VALUES ( :name , :world, :lobby, :respawn, :kit );
-- #  }

-- #  { delete-arena
-- #     :name string
DELETE FROM arenas WHERE name = :name;
-- #  }

-- #  { update-lobby
-- #     :name string
-- #     :lobby string
UPDATE arenas SET lobby = :lobby WHERE name = :name;
-- #  }

-- #  { update-respawn
-- #     :name string
-- #     :respawn string
UPDATE arenas SET respawn = :respawn WHERE name = :name;
-- #  }

-- #  { update-kit
-- #     :name string
-- #     :kit string
UPDATE arenas SET kit = :kit WHERE name = :name;
-- #  }

-- #  { add-kills
-- #     :player string
-- #     :kills int
INSERT INTO players(player, kills, deaths) VALUES ( :player , 1, 0 )
    ON CONFLICT(player) DO UPDATE SET kills = kills + :kills WHERE player = :player;
-- # &
UPDATE players SET kill_streak = kill_streak + 1 WHERE player = :player;
-- #  }

-- #  { add-deaths
-- #     :player string
-- #     :deaths int
INSERT INTO players(player, kills, deaths) VALUES ( :player , 0, 1 )
    ON CONFLICT(player) DO UPDATE SET deaths = deaths + :deaths WHERE player = :player;
-- # &
UPDATE players SET kill_streak = 0 WHERE player = :player;
-- #  }

-- #  { get-kills
-- #     :player string
SELECT kills FROM players WHERE player = :player;
-- #  }

-- #  { get-kill-streak
-- #     :player string
SELECT kill_streak FROM players WHERE player = :player;
-- #  }

-- #  { get-deaths
-- #     :player string
SELECT deaths FROM players WHERE player = :player;
-- #  }

-- #  { get-tops
-- #     :type string
-- #     :amount int 10
SELECT 
    player,
    CASE :type
        WHEN 'kills' THEN kills
        WHEN 'deaths' THEN deaths
        WHEN 'killstreak' THEN kill_streak
        ELSE 0
    END AS value
FROM players 
ORDER BY value DESC 
LIMIT :amount;
-- #  }

-- #  { get-player-info
-- #     :player string
SELECT kills, deaths, kill_streak FROM players WHERE player = :player;
-- #  }

-- #}