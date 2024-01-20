<?php

declare(strict_types=1);

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
use Generator;
use Laith98Dev\FFA\API;
use Laith98Dev\FFA\Main;
use Laith98Dev\FFA\utils\ClosureResult;
use Laith98Dev\FFA\utils\SQLKeyStorer;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\plugin\PluginOwned;

use pocketmine\utils\TextFormat as TF;

use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class FFACommand extends Command implements PluginOwned
{
	public function __construct(
		private Main $plugin
	){
		parent::__construct("ffa", "FFA Commands", null, ["freeforall"]);
		$this->setPermission("ffa.command");
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
				if(!$sender->hasPermission("ffa.command.admin")){
					return false;
				}
				if(!isset($args[1])){
					$sender->sendMessage(TF::RED . "Usage: /" . $cmdLabel . " create <arenaName>");
					return false;
				}
				
				$arenaName = $args[1];
				$world = $sender->getWorld();
				
				if($world->getFolderName() == $this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getFolderName()){
					$sender->sendMessage(TF::RED . "You cannot create a game in the default world!");
					return false;
				}

				Await::f2c(function () use($sender, $arenaName, $world): Generator{

					/**
					 * @var ClosureResult $isValid
					 */
					$isValid = yield from Await::promise(
						fn(Closure $resolve) => API::isValidArena($arenaName, $resolve)
					);

					if($isValid->getValue()){
						$sender->sendMessage(TF::RED . "There is an arena with this name.");
					} else {
						$data = [
							"name" => $arenaName,
							"world" => $world->getFolderName(),
							"lobby" => [],
							"respawn" => []
						];

						/**
						 * @var ClosureResult $response
						 */
						$response = yield from Await::promise(
							fn(Closure $resolve) => $this->getOwningPlugin()->addArena(
								$data,
								$resolve
							)
						);

						if($response->getValue()){
							$sender->sendMessage(TF::YELLOW . "Arena was created successfully.");
						} else {
							$sender->sendMessage(TF::RED . "There was an error while trying to add this arena; please check your console to see the error and contact the developer!");
						}
					}
				});
			break;
			
			case "remove":
				if(!$sender->hasPermission("ffa.command.admin")){
					return false;
				}
				
				if(!isset($args[1])){
					$sender->sendMessage(TF::RED . "Usage: /" . $cmdLabel . " remove <arenaName>");
					return false;
				}
				
				$arenaName = $args[1];

				Await::f2c(function () use($sender, $arenaName): Generator{
					/**
					 * @var ClosureResult $response
					 */
					$response = yield from Await::promise(
						fn(Closure $resolve) => $this->getOwningPlugin()->removeArena(
							$arenaName,
							$resolve
						)
					);

					if($response->getValue()){
						$sender->sendMessage(TF::GREEN . "Arena was deleted successfully!");
					} else {
						$sender->sendMessage(TF::RED . "Arena does not exist");
					}
				});

			break;
			
			case "setlobby":
				if(!$sender->hasPermission("ffa.command.admin")){
					return false;
				}

				Await::f2c(function () use ($sender, $cmdLabel): Generator{
					$world = $sender->getWorld();
					$arena = null;
					$arenaName = null;

					$rows = yield from Await::promise(
						fn(Closure $resolve) => $this->getOwningPlugin()->getProvider()->db()->executeSelect(
							SQLKeyStorer::GET_ARENAS,
							[],
							$resolve,
						)
					);

					if(!empty($rows)){
						foreach ($rows as $arena){
							if(strtolower($arena["world"]) == strtolower($world->getFolderName())){
								$arenaName = $arena["name"];
							}
						}

						if($arenaName === null){
							$sender->sendMessage(TF::RED . "Arena does not exist; try creating it using the command /" . $cmdLabel . " create!");
							return false;
						}

						$data = [
							"PX" => $sender->getLocation()->x,
							"PY" => $sender->getLocation()->y,
							"PZ" => $sender->getLocation()->z,
							"YAW" => $sender->getLocation()->yaw,
							"PITCH" => $sender->getLocation()->pitch
						];

						yield from Await::promise(
							fn(Closure $resolve) => $this->getOwningPlugin()->getProvider()->db()->executeChange(
								SQLKeyStorer::UPDATE_LOBBY,
								[
									"name" => $arenaName,
									"lobby" => json_encode($data)
								],
								$resolve
							)
						);

						$arenas_rows = yield from Await::promise(
							fn(Closure $resolve) => $this->getOwningPlugin()->getProvider()->db()->executeSelect(
								SQLKeyStorer::GET_ARENAS,
								[],
								$resolve
							)
						);

						if(!empty($arenas_rows)){
							foreach ($arenas_rows as $data){
								if(strtolower($data["name"]) == strtolower($arenaName)){
									$data["lobby"] = json_decode($data["lobby"], true);
									$data["respawn"] = json_decode($data["respawn"], true);

									if(($arena = $this->getOwningPlugin()->getArena($arenaName)) !== null){
										$arena->updateData($data);
									}
									
									$sender->sendMessage(TF::YELLOW . "successfully updated lobby position for '" . $arenaName . "'!");
									break;
								}
							}
						}
					}
				});
				
			break;
			
			case "setrespawn":
				if(!$sender->hasPermission("ffa.command.admin")){
					return false;
				}

				Await::f2c(function () use ($sender, $cmdLabel): Generator{
					$world = $sender->getWorld();
					$arena = null;
					$arenaName = null;

					$rows = yield from Await::promise(
						fn(Closure $resolve) => $this->getOwningPlugin()->getProvider()->db()->executeSelect(
							SQLKeyStorer::GET_ARENAS,
							[],
							$resolve,
						)
					);

					if(!empty($rows)){
						foreach ($rows as $arena){
							if(strtolower($arena["world"]) == strtolower($world->getFolderName())){
								$arenaName = $arena["name"];
							}
						}

						if($arenaName === null){
							$sender->sendMessage(TF::RED . "Arena does not exist; try creating it using the command /" . $cmdLabel . " create!");
							return false;
						}

						$data = [
							"PX" => $sender->getLocation()->x,
							"PY" => $sender->getLocation()->y,
							"PZ" => $sender->getLocation()->z,
							"YAW" => $sender->getLocation()->yaw,
							"PITCH" => $sender->getLocation()->pitch
						];

						yield from Await::promise(
							fn(Closure $resolve) => $this->getOwningPlugin()->getProvider()->db()->executeChange(
								SQLKeyStorer::UPDATE_RESPAWN,
								[
									"name" => $arenaName,
									"respawn" => json_encode($data)
								],
								$resolve
							)
						);

						$arenas_rows = yield from Await::promise(
							fn(Closure $resolve) => $this->getOwningPlugin()->getProvider()->db()->executeSelect(
								SQLKeyStorer::GET_ARENAS,
								[],
								$resolve
							)
						);

						if(!empty($arenas_rows)){
							foreach ($arenas_rows as $data){
								if(strtolower($data["name"]) == strtolower($arenaName)){
									$data["lobby"] = json_decode($data["lobby"], true);
									$data["respawn"] = json_decode($data["respawn"], true);

									if(($arena = $this->getOwningPlugin()->getArena($arenaName)) !== null){
										$arena->updateData($data);
									}
									
									$sender->sendMessage(TF::YELLOW . "successfully updated respawn position for '" . $arenaName . "'!");
									break;
								}
							}
						}
					}
				});

			break;
			
			case "list":
				if(!$sender->hasPermission("ffa.command.admin")){
					return false;
				}
				
				$sender->sendMessage(TF::GREEN . "Arenas:");
				foreach ($this->plugin->getArenas() as $arena){
					$sender->sendMessage(TF::YELLOW . "- " . $arena->getName() . " => Players: " . count($arena->getPlayers()));
				}
			break;
			
			case "join":
				if(isset($args[1])){
					$player = $sender;
					
					if(isset($args[2])){
						if(($pp = $this->getOwningPlugin()->getServer()->getPlayerByPrefix($args[2])) !== null){
							$player = $pp;
						}
					}
					
					if($this->getOwningPlugin()->joinArena($player, $args[1])){
						return true;
					}
				} else {
					if($this->getOwningPlugin()->joinRandomArena($sender)){
						return true;
					}
				}
			break;
			
			case "quit":
				if(($arena = $this->getOwningPlugin()->getPlayerArena($sender)) !== null){
					if($arena->quitPlayer($sender)){
						return true;
					}
				} else {
					$sender->sendMessage("You're not in an arena!");
					return false;
				}
			break;

			case "reload":
				if(!$sender->hasPermission("ffa.command.admin")){
					return false;
				}
				
				foreach ($this->getOwningPlugin()->getArenas() as $arena){
					foreach ($arena->getPlayers() as $player){
						$arena->quitPlayer($player);
					}
				}

				$this->getOwningPlugin()->loadKits();
				$this->getOwningPlugin()->loadArenas();

				$sender->sendMessage(TF::GREEN . "You've reloaded the plugin successfully!");
			break;

			default:
				$sender->sendMessage(TF::RED . "Usage: /" . $cmdLabel . " help");
			break;
		}
		
		return false;
	}
}
