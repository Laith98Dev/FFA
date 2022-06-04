<?php

namespace Laith98Dev\FFA\utils;

use Laith98Dev\FFA\game\FFAGame;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Utils {

    public static function messageFormat(string $msg, Player $player, FFAGame $game){
        $index = [
            "{PLAYER}" => $player->getName(),
            "{ARENA}" => $game->getName(),
            "{GAME}" => $game->getName(),
            "&" => TextFormat::ESCAPE,
            "{WORLD}" => $game->getWorld(),
            "{PLAYERS}" => count($game->getPlayers()),
            "{TIME}" => $game->getProtectTime($player)
        ];

        return str_replace(array_keys($index), array_values($index), $msg);
    }
}