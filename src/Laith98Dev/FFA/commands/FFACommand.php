<?php

namespace Laith98Dev\FFA\commands;

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
 *	Copyright (C) 2022 Laith98Dev
 *  
 *	Youtube: Laith Youtuber
 *	Discord: Laith98Dev#0695
 *	Github: Laith98Dev
 *	Email: help@laithdev.tk
 *	Donate: https://paypal.me/Laith113
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

use Laith98Dev\FFA\Main;
use Laith98Dev\FFA\utils\SQLKeyStorer;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\plugin\PluginOwned;

use pocketmine\utils\TextFormat as TF;

use pocketmine\player\Player;

class FFACommand extends Command implements PluginOwned
{

	public function __construct(
		private Main $plugin
		){
		parent::__construct("ffa", "FFA Commands", null, ["ffa"]);
		$this->setPermission("ffa.command.admin");
	}
	
	public function getOwningPlugin() : Main{
		return $this->plugin;
	}
	
	public function execute(CommandSender $sender, string $cmdLabel, array $args): bool{
		if(!($sender instanceof Player)){
			$sender->sendMessage("run command in-game only");
			return false;
		}
		
		if(!isset($args[0])){
			$sender->sendMessage(TF::RED . "Usage: /" . $cmdLabel . " help");
			return false;
		}
		
		switch ($args[0]){
			case "help":
				$sender->sendMessage(TF::YELLOW . "========================");
				if($sender->hasPermission("ffa.command.admin")){
					$sender->sendMessage(TF::GREEN  . "- /" . $cmdLabel . " help");
					$sender->sendMessage(TF::GREEN  . "- /" . $cmdLabel . " create");
					$sender->sendMessage(TF::GREEN  . "- /" . $cmdLabel . " remove");
					$sender->sendMessage(TF::GREEN  . "- /" . $cmdLabel . " setlobby");
					$sender->sendMessage(TF::GREEN  . "- /" . $cmdLabel . " setrespawn");
					$sender->sendMessage(TF::GREEN  . "- /" . $cmdLabel . " reload");
					$sender->sendMessage (TF::GREEN  . "- /" . $cmdLabel . " list");
				}
				$sender->sendMessage(TF::GREEN  . "- /" . $cmdLabel . " join");
				$sender->sendMessage(TF::GREEN  . "- /" . $cmdLabel . " quit");
				$sender->sendMessage(TF::YELLOW . "========================");
			break;
			
			case "create":
				if(!$sender->hasPermission("ffa.command.admin"))
					return false;
				if(!isset($args[1])){
					$sender->sendMessage(TF::RED . "Usage: /" . $cmdLabel . " create <arenaName>");
					return false;
				}
				
				$arenaName = $args[1];
				$level = $sender->getWorld();
				
				if($level->getFolderName() == $this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getFolderName()){
					$sender->sendMessage(TF::RED . "You cannot create game in default world!");
					return false;
				}

				$this->plugin->arena_Exist($arenaName, function (bool $exists) use ($sender, $arenaName, $level){
					if($exists){
						$sender->sendMessage(TF::RED . "Arena already exist!");
					} else {
						$data = ["name" => $arenaName, "world" => $level->getFolderName(), "lobby" => [], "respawn" => []];
						$this->plugin->addArena($data, function (bool $addRusult) use ($sender){
							if($addRusult){
								$sender->sendMessage(TF::YELLOW . "Arena created!");
							} else {
								$sender->sendMessage(TF::RED . "An error while trying to add this arena please contact with developer!");
							}
						});
					}
				});

			break;
			
			case "remove":
				if(!$sender->hasPermission("ffa.command.admin"))
					return false;
				
				if(!isset($args[1])){
					$sender->sendMessage(TF::RED . "Usage: /" . $cmdLabel . " remove <arenaName>");
					return false;
				}
				
				$arenaName = $args[1];

				$this->plugin->removeArena($arenaName, function (bool $deleted) use ($sender){
					if($deleted){
						$sender->sendMessage(TF::GREEN . "Arena deleted!");
					} else {
						$sender->sendMessage(TF::RED . "Arena not exist");
					}
				});

			break;
			
			case "setlobby":
				if(!$sender->hasPermission("ffa.command.admin"))
					return false;
				
				$level = $sender->getWorld();
				$arena = null;
				$arenaName = null;

				$this->plugin->getProvider()->db()->executeSelect(SQLKeyStorer::GET_ARENAS,
				[],
				function(array $rows) use ($level, $sender, $cmdLabel) {
					if(count($rows) > 0){
						$arenaName = null;
						foreach ($rows as $arena){
							if(strtolower($arena["world"]) == strtolower($level->getFolderName())){
								$arenaName = $arena["name"];
							}
						}

						if($arenaName == null){
							$sender->sendMessage(TF::RED . "Arena not exist, try create Usage: /" . $cmdLabel . " create" . "!");
							return false;
						}
						
						$data = ["PX" => $sender->getLocation()->x, "PY" => $sender->getLocation()->y, "PZ" => $sender->getLocation()->z, "YAW" => $sender->getLocation()->yaw, "PITCH" => $sender->getLocation()->pitch];
					
						$this->plugin->getProvider()->db()->executeChange(SQLKeyStorer::UPDATE_LOBBY,
						[
							"name" => $arenaName,
							"lobby" => json_encode($data)
						]);
		
						$this->plugin->getProvider()->db()->executeSelect(SQLKeyStorer::GET_ARENAS,
						[],
						function(array $rows) use ($sender, $arenaName) {
							if(count($rows) > 0){
								foreach ($rows as $data){
									if(strtolower($data["name"]) == strtolower($arenaName)){
										$data["lobby"] = json_decode($data["lobby"], true);
										$data["respawn"] = json_decode($data["respawn"], true);

										if(($arena = $this->plugin->getArena($arenaName)) !== null){
											$arena->UpdateData($data);
										}
										
										$sender->sendMessage(TF::YELLOW . "successfully updated lobby position for '" . $arenaName . "'!");
										break;
									}
								}
							}
						});
					}
				});
			break;
			
			case "setrespawn":
				if(!$sender->hasPermission("ffa.command.admin"))
					return false;
				
				$level = $sender->getWorld();
				$this->plugin->getProvider()->db()->executeSelect(SQLKeyStorer::GET_ARENAS,
				[],
				function(array $rows) use ($level, $sender, $cmdLabel) {
					if(count($rows) > 0){
						$arenaName = null;
						foreach ($rows as $arena){
							if(strtolower($arena["world"]) == strtolower($level->getFolderName())){
								$arenaName = $arena["name"];
							}
						}

						if($arenaName == null){
							$sender->sendMessage(TF::RED . "Arena not exist, try create Usage: /" . $cmdLabel . " create" . "!");
							return false;
						}

						$data = ["PX" => $sender->getLocation()->x, "PY" => $sender->getLocation()->y, "PZ" => $sender->getLocation()->z, "YAW" => $sender->getLocation()->yaw, "PITCH" => $sender->getLocation()->pitch];

						$this->plugin->getProvider()->db()->executeChange(SQLKeyStorer::UPDATE_RESPAWN,
						[
							"name" => $arenaName,
							"respawn" => json_encode($data)
						]);

						$this->plugin->getProvider()->db()->executeSelect(SQLKeyStorer::GET_ARENAS,
						[],
						function(array $rows) use ($sender, $arenaName) {
							if(count($rows) > 0){
								foreach ($rows as $data){
									if(strtolower($data["name"]) == strtolower($arenaName)){
										$data["lobby"] = json_decode($data["lobby"], true);
										$data["respawn"] = json_decode($data["respawn"], true);
										
										if(($arena = $this->plugin->getArena($arenaName)) !== null){
											$arena->UpdateData($data);
										}

										$sender->sendMessage(TF::YELLOW . "successfully updated respawn position for '" . $arenaName . "'!");
										break;
									}
								}
							}
						});
					}
				});

			break;
			
			case "list":
				if(!$sender->hasPermission("ffa.command.admin"))
					return false;
				
				$sender->sendMessage(TF::GREEN . "Arenas:");
				foreach ($this->plugin->getArenas() as $arena){
					$sender->sendMessage(TF::YELLOW . "- " . $arena->getName() . " => Players: " . count($arena->getPlayers()));
				}
			break;
			
			case "join":
				if(!$sender->hasPermission("ffa.command.join"))
					return false;
				if(isset($args[1])){
					$player = $sender;
					
					if(isset($args[2])){
						if(($pp = $this->plugin->getServer()->getPlayerByPrefix($args[2])) !== null){
							$player = $pp;
						}
					}
					
					if($this->plugin->joinArena($player, $args[1])){
						return true;
					}
				} else {
					if($this->plugin->joinRandomArena($sender)){
						return true;
					}
				}
			break;
			
			case "quit":
				if(!$sender->hasPermission("ffa.command.quit"))
					return false;
				if(($arena = $this->plugin->getPlayerArena($sender)) !== null){
					if($arena->quitPlayer($sender)){
						return true;
					}
				} else {
					$sender->sendMessage("You're not in a arena!");
					return false;
				}
			break;

			case "reload":
				if(!$sender->hasPermission("ffa.command.admin"))
					return false;
				
				foreach ($this->getOwningPlugin()->getArenas() as $arena){
					foreach ($arena->getPlayers() as $player){
						$arena->quitPlayer($player);
					}
				}

				$this->getOwningPlugin()->loadKits();
				$this->getOwningPlugin()->loadArenas();

				$sender->sendMessage(TF::GREEN . "You've been reloaded the plugin successfully!");
			break;

			default:
				$sender->sendMessage(TF::RED . "Usage: /" . $cmdLabel . " help");
			break;
		}
		
		return false;
	}
}
