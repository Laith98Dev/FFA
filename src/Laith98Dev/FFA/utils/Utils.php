<?php

declare(strict_types=1);

namespace Laith98Dev\FFA\utils;

/*  
 *  A plugin for PocketMine-MP.
 *  
 *	 _           _ _   _    ___   ___  _____             
 *	| |         (_) | | |  / _ \ / _ \|  __ \            
 *	| |     __ _ _| |_| |_| (_) | (_) | |  | | _____   __
 *	| |    / _` | | __| '_ \__, |> _ <| |  | |/ _ \ \ / /
 *	| |___| (_| | | |_| | | |/ /| (_) | |__| |  __/\ V / 
 *	|______\__,_|_|\__|_| |_/_/  \___/|_____/ \___| \_/  
 *	
 *	Copyright (C) 2025 Laith98Dev
 *  
 *  Youtube: Laith Youtuber
 *  Discord: Laith98Dev#0695 or @u.oo
 *  Github: Laith98Dev
 *  Email: spt.laithdev@gamil.com
 *  Donate: https://paypal.me/Laith113
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 	
 */

use Laith98Dev\FFA\game\Arena;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Utils
{
    public static function messageFormat(string $msg, Player $player, Arena $game): string
    {
        $index = [
            "{PLAYER}" => $player->getName(),
            "{ARENA}" => $game->getName(),
            "{GAME}" => $game->getName(),
            "&" => TextFormat::ESCAPE,
            "{WORLD}" => $game->getWorldName(),
            "{PLAYERS}" => strval(count($game->getPlayers())),
            "{TIME}" => strval($game->getProtectTime($player))
        ];

        return str_replace(array_keys($index), array_values($index), $msg);
    }
}
