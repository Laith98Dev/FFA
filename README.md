# FFA
- (FFA or Free For All) is a survival and fighting game where the game begins and you have to fight to survive.


# How to create a arena
- Teleport to the world who need make an arena in it.
- frist type `/ffa create "your arena name"` to create the arena.
- now go to arena lobby and type `/ffa setlobby` to set it.
- ok, now go to the respawn position and type `/ffa setrespawn` that will return to it after death (you can turn on/off from config)
- Great, you are now ready to play type `/ffa join "your arena name"` enjoy. if you want leave the game type `/ffa quit`

# the configure
- `scoreboardIp` : you can set to your server ip to show it in the game scoreboard
- `death-respawn-inMap` : that's will return the player to respawn position after death, you can set to `true` or `false`
- `join-and-respawn-protected` : that's will protect the player for 3 seconds after join and respawn
- `death-attack-message` : here you can set the death message when killed by someone
- `death-void-message` : and here you can set the death message when killed by void

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
`/ffa list` | `To see arenas list` | `ffa.command.admin`

# Other
- [![Donate](https://img.shields.io/youtube/views/SwzWwsrGG74?label=Tutorial&style=social)](https://www.youtube.com/watch?v=SwzWwsrGG74&ab_channel=LaithYoutuber)
- [![Donate](https://img.shields.io/badge/donate-Paypal-yellow.svg?style=flat-square)](https://paypal.me/Laith113)
