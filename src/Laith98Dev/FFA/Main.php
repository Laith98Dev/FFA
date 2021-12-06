<?php

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
 *	Copyright (C) 2021 Laith98Dev
 *  
 *	Youtube: Laith Youtuber
 *	Discord: Laith98Dev#0695
 *	Gihhub: Laith98Dev
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

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\world\Position;
use pocketmine\entity\Location;

use pocketmine\player\Player;
use pocketmine\math\Vector3;

use pocketmine\scheduler\Task;
use pocketmine\command\{CommandSender, Command};

use pocketmine\utils\{Config, TextFormat as TF};

class Main extends PluginBase implements Listener
{
	/** @var FFAGame[] */
	public $arenas = [];
	
	public function onEnable(): void{
		@mkdir($this->getDataFolder());
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new ArenasTask($this), 20);
		
		(new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"scoreboardIp" => "play.example.net",
			"death-respawn-inMap" => true,
			"join-and-respawn-protected" => true,
			"death-attack-message" => "&e{PLAYER} &fwas killed by &c{KILLER}",
			"death-void-message" => "&c{PLAYER} &ffall into void"
		]));
		
		$map = $this->getServer()->getCommandMap();
		
		$ffac = new FFACommand("ffa", "FFA Commands", null, ["ffa"]);
		$ffac->init($this);
		
		$map->register($this->getName(), $ffac);
		
		$this->reloadCheck();// TODO: quit all player when server reload^^
		$this->loadArenas();
	}
	
	public function reloadCheck(){
		foreach ($this->arenas as $arena){
			foreach ($arena->getPlayers() as $player){
				$arena->quitPlayer($player);
			}
		}
	}
	
	public function loadArenas(){
		$arenas = new Config($this->getDataFolder() . "arenas.yml", Config::YAML);
		foreach ($arenas->getAll() as $arena => $data){
			if(!isset($data["name"]) || !isset($data["world"]) || !isset($data["lobby"]) || !isset($data["respawn"])){
				if(isset($data["name"]))
					$this->getLogger()->error("Error in load arena " . $data["name"] . " because corrupt data!");
				continue;
			}
			
			$this->getServer()->getWorldManager()->loadWorld($data["world"]);
			if(($level = $this->getServer()->getWorldManager()->getWorldByName($data["world"])) !== null){
				$level->setTime(1000);
				$level->stopTime();
			}
			$this->arenas[$data["name"]] = new FFAGame($this, $data);
		}
	}
	
	public function addArena(array $data): bool{
		if(!isset($data["name"]) || !isset($data["world"]) || !isset($data["lobby"]) || !isset($data["respawn"]))
			return false;
		
		$name = $data["name"];
		$world = $data["world"];
		$lobby = $data["lobby"];
		$respawn = $data["respawn"];
		
		$arenas = new Config($this->getDataFolder() . "arenas.yml", Config::YAML);
		
		if($arenas->get($name))
			return false;
		
		$arenas->set($name, $data);
		$arenas->save();
		
		$this->arenas[$name] = new FFAGame($this, $data);
		return true;
	}
	
	public function removeArena(string $name): bool{
		$arenas = new Config($this->getDataFolder() . "arenas.yml", Config::YAML);
		
		if(!$arenas->get($name) || !isset($this->arenas[$name]))
			return false;
		
		if(($arena = $this->getArena($name)) !== null){
			foreach ($arena->getPlayers() as $player){
				$arena->quitPlayer($player);
			}
		}
		
		$arenas->removeNested($name);
		$arenas->save();
		
		unset($this->arenas[$name]);
		return true;
	}
	
	public function getArenas(){
		return $this->arenas;
	}
	
	public function getArena(string $name){
		return isset($this->arenas[$name]) ? $this->arenas[$name] : null;
	}
	
	public function joinArena(Player $player, string $name): bool{
		if(($arena = $this->getArena($name)) == null){
			$player->sendMessage(TF::RED . "Arena not exist!");
			return false;
		}
		
		if($this->getPlayerArena($player) !== null){
			$player->sendMessage(TF::RED . "You're already in arena!");
			return false;
		}
		
		if($arena->joinPlayer($player)){
			return true;
		}
		return false;
	}
	
	public function joinRandomArena(Player $player): bool{
		if($this->getPlayerArena($player) !== null){
			$player->sendMessage(TF::RED . "You're already in arena!");
			return false;
		}
		
		if(count($this->getArenas()) == 0){
			$player->sendMessage(TF::RED . "No arenas found!");
			return false;
		}
		
		$all = [];
		foreach ($this->getArenas() as $arena){
			$all[] = $arena->getName();
		}
		
		shuffle($all);
		shuffle($all);
		
		$rand = mt_rand(0, (count($all) - 1));
		
		$final = null;
		$i = 0;
		foreach ($all as $aa){
			if($i == $rand){
				$final = $aa;
			}
			$i++;
		}
		
		if($final !== null){
			if($this->joinArena($player, $final)){
				return true;
			}
		}
		
		return false;
	}
	
	public function getPlayerArena(Player $player){
		$arena = null;
		
		foreach ($this->getArenas() as $a){
			if($a->inArena($player)){
				$arena = $a;
			}
		}
		
		return $arena;
	}
	
	public function onDrop(PlayerDropItemEvent $event){
		$player = $event->getPlayer();
		if($player instanceof Player){
			if(($arena = $this->getPlayerArena($player)) !== null){
				$event->cancel();
			}
		}
	}
	
	public function onHunger(PlayerExhaustEvent $event){
		$player = $event->getPlayer();
		if($player instanceof Player){
			if(($arena = $this->getPlayerArena($player)) !== null){
				$event->cancel();
			}
		}
	}
	
	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if($player instanceof Player){
			if(($arena = $this->getPlayerArena($player)) !== null){
				$arena->quitPlayer($player);
			}
		}
	}
	
	public function onLevelChange(EntityTeleportEvent $event){
		$player = $event->getEntity();
		$from = $event->getFrom();
		$to = $event->getTo();
		if($player instanceof Player){
			if(($arena = $this->getPlayerArena($player)) !== null && $to->getWorld()->getFolderName() !== $to->getWorld()->getFolderName()){
				$arena->quitPlayer($player);
			}
		}
	}
	
	public function onPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if($player instanceof Player){
			if(($arena = $this->getPlayerArena($player)) !== null){
				if(in_array($player->getGamemode(), [0, 2])){
					$event->cancel();
				}
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		if($player instanceof Player){
			if(($arena = $this->getPlayerArena($player)) !== null){
				if(in_array($player->getGamemode(), [0, 2])){
					$event->cancel();
				}
			}
		}
	}
	
	public function onDamage(EntityDamageEvent $event): void{
		$entity = $event->getEntity();
		if($entity instanceof Player){
			if(($arena = $this->getPlayerArena($entity)) !== null){
				if($event->getCause() == 4){
					$event->cancel();
					return;
				}
				
				if($entity->getHealth() <= $event->getFinalDamage()){
					$arena->killPlayer($entity);
					$event->cancel();
					return;
				}
				
				if($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player){
					if($arena->isProtected($entity)){
						$event->cancel();
					}
				}
			}
		}
	}
	
	public function addKill(Player $player, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($player->getName());
		$p["kills"] = ($p["kills"] + $add);
		$tops->set($player->getName(), $p);
		$tops->save();
	}
	
	public function addKillByName(string $name, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($name);
		$p["kills"] = ($p["kills"] + $add);
		$tops->set($name, $p);
		$tops->save();
	}
	
	public function addDeath(Player $player, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($player->getName());
		$p["deaths"] = ($p["deaths"] + $add);
		$tops->set($player->getName(), $p);
		$tops->save();
	}
	
	public function addDeathByName(string $name, int $add = 1){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		$p = $tops->get($name);
		$p["deaths"] = ($p["deaths"] + $add);
		$tops->set($name, $p);
		$tops->save();
	}
	
	public function getKills(Player $player){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($player->getName())["kills"];
	}
	
	public function getKillsByName(string $name){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($name)["kills"];
	}
	
	public function getDeaths(Player $player){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($player->getName())){
			$tops->set($player->getName(), ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($player->getName())["deaths"];
	}
	
	public function getDeathsByName(string $name){
		$tops = new Config($this->getDataFolder() . "tops.yml", Config::YAML);
		if(!$tops->get($name)){
			$tops->set($name, ["kills" => 0, "deaths" => 0]);
			$tops->save();
		}
		
		return $tops->get($name)["deaths"];
	}
}
