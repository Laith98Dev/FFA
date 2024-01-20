<?php

declare(strict_types=1);

namespace Laith98Dev\FFA;

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
 *	Copyright (C) 2024 Laith98Dev
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

use Closure;
use Laith98Dev\FFA\utils\ClosureResult;
use Laith98Dev\FFA\utils\SQLKeyStorer;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;

class API
{
    /**
     * This function allows you to add a specific count of kills to a specific player.
     *
     * @param Player|string $player
     * @param integer       $amount
     * @param Closure|null  $onSuccess : <code>function(ClosureResult $result) : void{}</code>
     * @return void
     */
    public static function addKill(Player|string $player, int $amount, Closure $onSuccess): void{
		$name = $player instanceof Player ? $player->getName() : $player;
        Main::getInstaance()->getProvider()->db()->executeChange(
            SQLKeyStorer::ADD_KILLS,
            [
                "player" => $name,
                "kills" => $amount,
            ],
            fn(int $affectedRows) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_SUCCESS,
                    $affectedRows
                )
            ),
            fn(SqlError $err) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_FAILURE,
                    $err->getMessage()
                )
            )
        );
	}
	
    /**
     * This function allows you to add a specific count of deaths to a specific player.
     *
     * @param Player|string $player
     * @param integer       $amount
     * @param Closure       $onSuccess : <code>function(ClosureResult $result) : void{}</code>
     * @return void
     */
	public static function addDeath(Player|string $player, int $amount, Closure $onSuccess){
        $name = $player instanceof Player ? $player->getName() : $player;
        Main::getInstaance()->getProvider()->db()->executeChange(
            SQLKeyStorer::ADD_DEATHS,
            [
                "player" => $name,
                "deaths" => $amount,
            ],
            fn(int $affectedRows) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_SUCCESS,
                    $affectedRows
                )
            ),
            fn(SqlError $err) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_FAILURE,
                    $err->getMessage()
                )
            )
        );
	}
	
    /**
     * This function allows you to get the kill count of a specific player.
     *
     * @param Player|string $player
     * @param Closure       $onSuccess : <code>function(ClosureResult $result) : void{}</code>
     * @return void
     */
	public static function getKills(Player|string $player, Closure $onSuccess){
        $name = $player instanceof Player ? $player->getName() : $player;
        Main::getInstaance()->getProvider()->db()->executeSelect(
            SQLKeyStorer::GET_KILLS,
            [
                "player" => $name
            ],
            fn(array $rows) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_SUCCESS,
                    (isset($rows[0]) ? $rows[0]["kills"] : 0)
                )
            ),
            fn(SqlError $err) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_FAILURE,
                    $err->getMessage()
                )
            )
        );
	}
	
    /**
     * This function allows you to get the death count of a specific player.
     *
     * @param Player|string $player
     * @param Closure       $onSuccess : <code>function(ClosureResult $result) : void{}</code>
     * @return void
     */
	public static function getDeaths(Player|string $player, Closure $onSuccess){
        $name = $player instanceof Player ? $player->getName() : $player;
        Main::getInstaance()->getProvider()->db()->executeSelect(
            SQLKeyStorer::GET_DEATHS,
            [
                "player" => $name
            ],
            fn(array $rows) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_SUCCESS,
                    (isset($rows[0]) ? $rows[0]["deaths"] : 0)
                )
            ),
            fn(SqlError $err) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_FAILURE,
                    $err->getMessage()
                )
            )
        );
	}

    /**
     * This function allows you to check if the arena exists.
     *
     * @param string        $name
     * @param Closure       $onSuccess : <code>function(ClosureResult $result) : void{}</code>
     * @return void
     */
    public static function isValidArena(string $name, Closure $onSuccess): void
    {
        Main::getInstaance()->getProvider()->db()->executeSelect(
            SQLKeyStorer::GET_ARENA,
            [
                "name" => $name
            ],
            fn(array $rows) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_SUCCESS,
                    !empty($rows)
                )
            ),
            fn(SqlError $err) => $onSuccess(
                ClosureResult::create(
                    ClosureResult::STATE_FAILURE,
                    $err->getMessage()
                )
            )
        );
    }
}