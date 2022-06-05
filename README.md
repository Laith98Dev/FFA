
<div align="center">
	<h1>Free For All</h1>
  <h3>(FFA or Free For All) is a survival and fighting game where the game begins and you have to fight to survive.</h3>
</div>

# How to create a arena
- Teleport to the world who need make an arena in it.
- frist type `/ffa create "your arena name"` to create the arena.
- now go to arena lobby and type `/ffa setlobby` to set it.
- ok, now go to the respawn position and type `/ffa setrespawn` that will return to it after death (you can turn on/off from config)
- Use `/ffa reload` to load the arena.
- Great, you are now ready to play type `/ffa join "your arena name"` enjoy. if you want leave the game type `/ffa quit`

# the configure
- <h1>Formats</h1> 
- `{PLAYER}` : to get the player name
- `{ARENA} | {GAME}` : to get the arena name
- `&` : same as `§`
- `{WORLD}` : to get arena world name
- `{PLAYERS}` : to get arena players count
- `{TIME}` : to get player protected time left
- <h1>General</h1>
- `scoreboardIp` : you can set to your server ip to show it in the game scoreboard
- `banned-commands`: you can add the commands who want to banned in the game
- `death-respawn-inMap` : that's will return the player to respawn position after death, you can set to `true` or `false`
- `join-and-respawn-protected` : that's will protect the player for 3 seconds after join and respawn
- `protected-time` : to can edit the protect time 
- `protected-message` : to edit protect message
- `death-attack-message` : here you can set the death message when killed by someone
- `death-void-message` : and here you can set the death message when killed by void
- `join-message` : to edit player join message
- `leave-message` : to edit player leave message
- `kills-messages` : to add/remove kill messages, this message will send to player every 5 kills automatically
- `scoreboard-title` : to edit scoreboard title name
- `provider` : currently now it's support `sqlit3` only do not change it
- `database` : do not change anything
- `kits` : you can edit the default kit right now, example:
```yaml
kits:
  default:
    slot-0:
      id: 267
      meta: 0
      count: 1
      enchants: []
    slot-1:
      id: 322
      meta: 0
      count: 5
      enchants: []
    helmet:
      id: 306
      enchants: []
    chestplate:
      id: 307
      enchants:
        id-0:
          level: 2
    leggings:
      id: 308
      enchants: []
    boots:
      id: 309
      enchants: []
```

# Commands
Command | Description | Permission
--- | --- | ---
`/ffa join <ArenaName:optional>` | `To join a specific or random arena` | `No permission`
`/ffa quit` | `To leave the arena` | `No permission`
`/ffa help` | `To see commands list` | `ffa.command.admin`
`/ffa create` | `To create a new arena` | `ffa.command.admin`
`/ffa remove` | `To delete a specific arena` | `ffa.command.admin`
`/ffa setlobby` | `To set lobby position in arena` | `ffa.command.admin`
`/ffa setrespawn` | `To set respawn position in arena` | `ffa.command.admin`
`/ffa reload` | `re-loaded the kits and arenas` | `ffa.command.admin`
`/ffa list` | `To see arenas list` | `ffa.command.admin`

# API
```php
use Laith98Dev\FFA\Main as FFA;

// add kills to player 
FFA::getInstaance()->addKill(Player, $amount);

// add kills to player by name
FFA::getInstaance()->addKillByName("player name", $amount);

// add deaths to player 
FFA::getInstaance()->addDeath(Player, $amount);

// add deaths to player by name
FFA::getInstaance()->addDeathByName("player name", $amount);

// get player kills
FFA::getInstaance()->getKills(Player, function (int $kills){
  // TODO
});

// get player kills by name
FFA::getInstaance()->getKillsByName("player name", function (int $kills){
  // TODO
});

// get player deaths
FFA::getInstaance()->getDeaths(Player, function (int $deaths){
  // TODO
});

// get player deaths by name
FFA::getInstaance()->getDeathsByName("player name", function (int $deaths){
  // TODO
});


```

# Other
- [![tutorial](https://img.shields.io/youtube/views/SwzWwsrGG74?label=Tutorial&style=social)](https://www.youtube.com/watch?v=SwzWwsrGG74&ab_channel=LaithYoutuber)
- [![Donate](https://img.shields.io/badge/donate-Paypal-yellow.svg?style=flat-square)](https://paypal.me/Laith113)
