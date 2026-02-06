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

class SQLKeyStorer
{
    public const PREFIX = "ffa.";

    public const INIT = self::PREFIX . "init";

    public const ADD_KIT_COLUMN = self::PREFIX . "add-kit-column";
    public const ADD_KIT_STREAK_COLUMN = self::PREFIX . "add-kill-streak-column";

    public const ADD_ARENA = self::PREFIX . "add-arena";
    public const DELETE_ARENA = self::PREFIX . "delete-arena";
    public const GET_ARENA = self::PREFIX . "get-arena";
    public const GET_ARENAS = self::PREFIX . "get-arenas";
    
    public const GET_TOPS = self::PREFIX . "get-tops";
    public const GET_PLAYER_INFO = self::PREFIX . "get-player-info";

    public const ADD_KILLS = self::PREFIX . "add-kills";
    public const ADD_DEATHS = self::PREFIX . "add-deaths";

    public const GET_KILLS = self::PREFIX . "get-kills";
    public const GET_KILL_STREAK = self::PREFIX . "get-kill-streak";
    public const GET_DEATHS = self::PREFIX . "get-deaths";

    public const UPDATE_LOBBY = self::PREFIX . "update-lobby";
    public const UPDATE_RESPAWN = self::PREFIX . "update-respawn";
    public const UPDATE_KIT = self::PREFIX . "update-kit";
}
