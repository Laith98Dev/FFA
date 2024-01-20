
<div align="center">
	<h1>Free For All</h1>
  <h3>(FFA, or Free for All) is a survival and fighting game where the game begins and you have to fight to survive.</h3>
</div>

# Dependencies
- [BanCommands](https://poggit.pmmp.io/p/BanCommands)

# How to create an arena
- Teleport to the world who need to make an arena in it.
- First, type `/ffa create "your arena name"` to create the arena.
- Now go to the arena lobby and type `/ffa setlobby` to set it.
- Okay, now go to the respawn position and type `/ffa setrespawn` that will return to it after death (you can turn it on or off from the config).
- Use `/ffa reload` to load the arena.
- Great, you are now ready to play. Type `/ffa join "your arena name."` enjoy. If you want to leave the game, type `/ffa quit'.

# the configure
- <h1>Formats</h1> 
- `{PLAYER}` : to get the player's name
- `{ARENA} or {GAME}` : to get the arena name
- `&` : same as `§`
- `{WORLD}` : to get the arena's world name
- `{PLAYERS}` : to get arena players count
- `{TIME}` : to get player protected time left
- <h1>General</h1>
- `scoreboardIp` : You can set your server IP to show it on the game scoreboard.
- `banned-commands`: You can add the commands you want banned in the game.
- `death-respawn-inMap` : That will return the player to the respawn position after death; you can set it to `true` or `false`.
- `join-and-respawn-protected` : that will protect the player for 3 seconds after joining and respawning.
- `protected-time` : to edit the protected time.
- `protected-message` : to edit protect message.
- `death-attack-message` : Here, you can set the death message when killed by someone.
- `death-void-message` : and here you can set the death message when killed by void.
- `join-message` : to edit player join message.
- `leave-message` : to edit player leave message
- `kills-messages` : To add or remove kill messages, this message will be sent to the player every 5 kills automatically.
- `scoreboard-title` : to edit the scoreboard title name.
- `provider` : Currently, it's supported `sqlite3` only; do not change it.
- `database` : Do not change anything.
- `kits` : You can edit the default kit right now, for example:
```yaml
kits:
  default:
    slot-0:
      id: iron_sword
      count: 1
      enchants: []
    slot-1:
      id: golden_apple
      count: 5
      enchants: []
    slot-2:
      id: bow
      count: 1
      enchants: []
    slot-3:
      id: arrow
      count: 15
      enchants: []
    helmet:
      id: iron_helmet
      enchants: []
    chestplate:
      id: iron_chestplate
      enchants:
        id-0:
          level: 2
    leggings:
      id: iron_leggings
      enchants: []
    boots:
      id: iron_boots
      enchants: []
```

# Commands
Command | Description | Permission
--- | --- | ---
`/ffa join <ArenaName:optional>` | `To join a specific or random arena` | `No permission`
`/ffa quit` | `To leave the arena` | `No permission`
`/ffa help` | `To see the command list` | `ffa.command.admin`
`/ffa create` | `To create a new arena` | `ffa.command.admin`
`/ffa remove` | `To delete a specific arena` | `ffa.command.admin`
`/ffa setlobby` | `To set the lobby position in the arena` | `ffa.command.admin`
`/ffa setrespawn` | `To set the respawn position in the arena` | `ffa.command.admin`
`/ffa reload` | `re-loaded the kits and arenas` | `ffa.command.admin`
`/ffa list` | `To see the arenas list` | `ffa.command.admin`

# API
As of v2.0.0, all the API functions have moved to [API](https://github.com/Laith98Dev/FFA/blob/main/src/Laith98Dev/FFA/API.php).
```php
use Laith98Dev\FFA\API;
use Laith98Dev\FFA\utils\ClosureResult;

// add kills to the player
API::addKill($PlayerOrPlayerName, $amount, function (ClosureResult $result){
    if($result->getStatus() == ClosureResult::STATE_SUCCESS){
        echo "Added `$amount` kills to the player successfully." . PHP_EOL;
    } else {
        echo "Failed to add kills to the player; reason: " . $result->getValue() . PHP_EOL;
    }
});

// add deaths to player
API::addDeath($PlayerOrPlayerName, $amount, function (ClosureResult $result){
    if($result->getStatus() == ClosureResult::STATE_SUCCESS){
        echo "Added `$amount` deaths to the player successfully." . PHP_EOL;
    } else {
        echo "Failed to add deaths to the player; reason: " . $result->getValue() . PHP_EOL;
    }
});

// get player kills
API::getKills($PlayerOrPlayerName, function (ClosureResult $result){
    if($result->getStatus() == ClosureResult::STATE_SUCCESS){
        echo "Player kills is " . $result->getValue() . PHP_EOL;
    } else {
        echo "Failed to get player kills; reason: " . $result->getValue() . PHP_EOL;
    }
});

// get player deaths
API::getDeaths($PlayerOrPlayerName, function (ClosureResult $result){
    if($result->getStatus() == ClosureResult::STATE_SUCCESS){
        echo "Player deaths is " . $result->getValue() . PHP_EOL;
    } else {
        echo "Failed to get player deaths; reason: " . $result->getValue() . PHP_EOL;
    }
});

// Check if an arena exists
API::isValidArena($arena_name, function (ClosureResult $result){
    if($result->getValue()){
        echo "Arena exists" . PHP_EOL;
    } else {
        echo "Arena doesn't exist." . PHP_EOL;
    }
});

```

# Other
- [![tutorial](https://img.shields.io/youtube/views/SwzWwsrGG74?label=Tutorial&style=social)](https://www.youtube.com/watch?v=SwzWwsrGG74&ab_channel=LaithYoutuber)
- [![Donate](https://img.shields.io/badge/donate-Paypal-yellow.svg?style=flat-square)](https://paypal.me/Laith113)
