<?php

declare(strict_types=1);

namespace Laith98Dev\FFA\tasks;

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

use pocketmine\scheduler\Task;

use Laith98Dev\FFA\Main;

class ArenasTask extends Task
{
	public function __construct(
		private Main $plugin
	) {
		// NOOP
	}

	public function onRun(): void
	{
		foreach ($this->plugin->getArenas() as $arena) {
			$arena->tick();
		}
	}
}
