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

use pocketmine\level\Location;

use pocketmine\item\Item;

use pocketmine\Player;

use pocketmine\math\Vector3;

use pocketmine\level\Position;

use pocketmine\utils\{Config, TextFormat as TF};

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;

class FFAGame 
{
	/** @var Main */
	private $plugin;
	
	/** @var array */
	private $data;
	
	/** @var string[] */
	private $players = [];
	
	/** @var string[] */
	private $scoreboards = [];
	
	/** @var int */
	private $scoreboardsLine = 0;
	
	private $scoreboardsLines = [
		0 => TF::BOLD . TF::YELLOW . "FFA",
		1 => TF::BOLD . TF::WHITE . "F" . TF::YELLOW . "FA",
		2 => TF::BOLD . TF::YELLOW . "F" . TF::WHITE . "F" . TF::YELLOW . "A",
		3 => TF::BOLD . TF::YELLOW . "FF" . TF::WHITE . "A",
		4 => TF::BOLD . TF::WHITE . "FFA"
	];
	
	public $protect = [];
	
	public function __construct(Main $plugin, array $data){
		$this->plugin = $plugin;
		$this->UpdateData($data);
	}
	
	public function getPlugin(){
		return $this->plugin;
	}
	
	public function UpdateData(array $data){
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function getName(){
		return $this->getData()["name"];
	}
	
	public function getWorld(){
		return $this->getData()["world"];
	}
	
	public function getLobby(){
		return $this->getData()["lobby"];
	}
	
	public function getRespawn(){
		return $this->getData()["respawn"];
	}
	
	public function getPlayers(){
		return $this->players;
	}
	
	public function isProtected(Player $player){
		return isset($this->protect[$player->getName()]);
	}
	
	public function inArena(Player $player){
		return isset($this->players[$player->getName()]) ? true : false;
	}
	
	public function new(Player $player, string $objectiveName, string $displayName): void{
		if(isset($this->scoreboards[$player->getName()])){
			$this->remove($player);
		}
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$player->sendDataPacket($pk);
		$this->scoreboards[$player->getName()] = $objectiveName;
	}

	public function remove(Player $player): void{
		$objectiveName = $this->getObjectiveName($player) ?? "ffa";
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objectiveName;
		$player->sendDataPacket($pk);
		unset($this->scoreboards[$player->getName()]);
	}

	public function setLine(Player $player, int $score, string $message): void{
		if(!isset($this->scoreboards[$player->getName()])){
			$this->plugin->getLogger()->error("Cannot set a score to a player with no scoreboard");
			return;
		}
		if($score > 15 || $score < 1){
			$this->plugin->getLogger()->error("Score must be between the value of 1-15. $score out of range");
			return;
		}
		$objectiveName = $this->getObjectiveName($player) ?? "ffa";
		$entry = new ScorePacketEntry();
		$entry->objectiveName = $objectiveName;
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $message;
		$entry->score = $score;
		$entry->scoreboardId = $score;
		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$player->sendDataPacket($pk);
	}

	public function getObjectiveName(Player $player): ?string{
		return isset($this->scoreboards[$player->getName()]) ? $this->scoreboards[$player->getName()] : null;
	}
	
	public function getLevel(?string $name = null){
		if($name == null){
			$this->plugin->getServer()->loadLevel($this->getWorld());
			return $this->plugin->getServer()->getLevelByName($this->getWorld());
		}
		return $this->plugin->getServer()->getLevelByName($name);
	}
	
	public function broadcast(string $message){
		foreach ($this->getPlayers() as $player){
			$player->sendMessage($message);
		}
	}
	
	public function joinPlayer(Player $player): bool{
		
		if(isset($this->players[$player->getName()]))
			return false;
		
		$lobby = $this->getLobby();
		
		if(!is_array($lobby) || count($lobby) == 0){
			if($player->hasPermission("ffa.command.admin"))
				$player->sendMessage(TF::RED . "Please set lobby position, Usage: /ffa setlobby");
			return false;
		}
		
		if(!is_array($this->getRespawn()) || count($this->getRespawn()) == 0){
			if($player->hasPermission("ffa.command.admin"))
				$player->sendMessage(TF::RED . "Please set respawn position, Usage: /ffa setrespawn");
			return false;
		}
		
		$x = $lobby["PX"];
		$y = $lobby["PY"];
		$z = $lobby["PZ"];
		$yaw = $lobby["YAW"];
		$pitch = $lobby["PITCH"];
		
		$player->teleport(new Position($x, $y, $z, $this->getLevel()), $yaw, $pitch);
		
		$player->setGamemode(2);
		$player->setHealth(20);
		$player->setFood(20);
		
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();
		$player->removeAllEffects();
		
		$player->getInventory()->setItem(0, Item::get(Item::IRON_SWORD, 0, 1));
		$player->getInventory()->setItem(1, Item::get(Item::GOLDEN_APPLE, 0, 5));
		$player->getInventory()->setItem(2, Item::get(Item::BOW, 0, 1));
		$player->getInventory()->setItem(3, Item::get(Item::ARROW, 0, 15));
		
		$player->getArmorInventory()->setHelmet(Item::get(Item::IRON_HELMET));
		$player->getArmorInventory()->setChestplate(Item::get(Item::IRON_CHESTPLATE));
		$player->getArmorInventory()->setLeggings(Item::get(Item::IRON_LEGGINGS));
		$player->getArmorInventory()->setBoots(Item::get(Item::IRON_BOOTS));
		
		$this->players[$player->getName()] = $player;
		
		$cfg = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
		if($cfg->get("join-and-respawn-protected") === true){
			$this->protect[$player->getName()] = 3;
			$player->sendMessage("You're now protected 3 seconds");
		}
		
		$this->broadcast($player->getName() . " joined to FFA!");
		return true;
	}
	
	public function quitPlayer(Player $player): bool{
		
		if(!isset($this->players[$player->getName()]))
			return false;
		
		unset($this->players[$player->getName()]);
		
		$this->remove($player);
		
		$player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();
		$player->removeAllEffects();
		$player->setGamemode($this->plugin->getServer()->getDefaultGamemode());
		$player->setHealth(20);
		$player->setFood(20);
		
		$this->broadcast($player->getName() . " quit FFA!");
		return true;
	}
	
	public function killPlayer(Player $player): void{
		$message = null;
		$event = $player->getLastDamageCause();
		
		if($event == null)
			return;
		
		if(!is_int($event->getCause()))
			return;
		
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();
		$player->removeAllEffects();
		
		$player->setGamemode(2);
		$player->setHealth(20);
		$player->setFood(20);
		$this->plugin->addDeath($player);
		$cfg = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
		switch ($event->getCause()){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				$damager = $event instanceof EntityDamageByEntityEvent ? $event->getDamager() : null;
				if($damager !== null && $damager instanceof Player){
					$message = str_replace(["{PLAYER}", "{KILLER}", "&"], [$player->getName(), $damager->getName(), TF::ESCAPE], $cfg->get("death-attack-message"));
					$this->plugin->addKill($damager);
					
					$damager->sendPopup(TF::YELLOW . "+1 Kill");
					$damager->setHealth(20);
					$damager->setFood(20);
					
					$damager->getInventory()->clearAll();
					$damager->getArmorInventory()->clearAll();
					$damager->getCraftingGrid()->clearAll();
					$damager->removeAllEffects();
					
					$damager->getInventory()->setItem(0, Item::get(Item::IRON_SWORD, 0, 1));
					$damager->getInventory()->setItem(1, Item::get(Item::GOLDEN_APPLE, 0, 5));
					$damager->getInventory()->setItem(2, Item::get(Item::BOW, 0, 1));
					$damager->getInventory()->setItem(3, Item::get(Item::ARROW, 0, 15));
					
					$damager->getArmorInventory()->setHelmet(Item::get(Item::IRON_HELMET));
					$damager->getArmorInventory()->setChestplate(Item::get(Item::IRON_CHESTPLATE));
					$damager->getArmorInventory()->setLeggings(Item::get(Item::IRON_LEGGINGS));
					$damager->getArmorInventory()->setBoots(Item::get(Item::IRON_BOOTS));
				}
			break;
			
			case EntityDamageEvent::CAUSE_VOID:
				$message = str_replace(["{PLAYER}", "&"], [$player->getName(), TF::ESCAPE], $cfg->get("death-void-message"));
			break;
		}
		
		if($message !== null)
			$this->broadcast($message);
		
		if($cfg->get("death-respawn-inMap") === true){
			$this->respawn($player);
		} else {
			$this->quitPlayer($player);
		}
	}
	
	public function respawn(Player $player){
		$player->setGamemode(2);
		$player->setHealth(20);
		$player->setFood(20);
		
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();
		$player->removeAllEffects();
		
		$player->getInventory()->setItem(0, Item::get(Item::IRON_SWORD, 0, 1));
		$player->getInventory()->setItem(1, Item::get(Item::GOLDEN_APPLE, 0, 5));
		$player->getInventory()->setItem(2, Item::get(Item::BOW, 0, 1));
		$player->getInventory()->setItem(3, Item::get(Item::ARROW, 0, 15));
		
		$player->getArmorInventory()->setHelmet(Item::get(Item::IRON_HELMET));
		$player->getArmorInventory()->setChestplate(Item::get(Item::IRON_CHESTPLATE));
		$player->getArmorInventory()->setLeggings(Item::get(Item::IRON_LEGGINGS));
		$player->getArmorInventory()->setBoots(Item::get(Item::IRON_BOOTS));
		
		$respawn = $this->getRespawn();
		$x = $respawn["PX"];
		$y = $respawn["PY"];
		$z = $respawn["PZ"];
		$yaw = $respawn["YAW"];
		$pitch = $respawn["PITCH"];
		
		$player->teleport(new Position($x, $y, $z, $this->getLevel()), $yaw, $pitch);
		
		$cfg = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
		if($cfg->get("join-and-respawn-protected") === true){
			$this->protect[$player->getName()] = 3;
			$player->sendMessage("You're now protected 3 seconds");
		}
		
		$player->addTitle(TF::YELLOW . TF::BOLD . "Respawned");
	}
	
	public function tick(){
		foreach ($this->getPlayers() as $player){
			$cfg = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
			$this->new($player, "ffa", $this->scoreboardsLines[$this->scoreboardsLine]);
			$this->setLine($player, 1, " ");
			$this->setLine($player, 2, " Players: " . TF::YELLOW . count($this->getPlayers()) . "  ");
			$this->setLine($player, 3, "  ");
			$this->setLine($player, 4, " Map: " . TF::YELLOW . $this->getWorld() . "  ");
			$this->setLine($player, 5, "   ");
			$this->setLine($player, 6, " Kills: " . TF::YELLOW . $this->plugin->getKills($player) . " ");
			$this->setLine($player, 7, " Deaths: " . TF::YELLOW . $this->plugin->getDeaths($player) . " ");
			$this->setLine($player, 8, "    ");
			$this->setLine($player, 9, " " . $cfg->get("scoreboardIp", "play.example.net") . " ");
		}
		
		if($this->scoreboardsLine == (count($this->scoreboardsLines) - 1)){
			$this->scoreboardsLine = 0;
		} else {
			++$this->scoreboardsLine;
		}
		
		foreach ($this->protect as $name => $time){
			//var_dump("Player: " . $name . " Time: " . $time . "\n");
			if($time == 0){
				unset($this->protect[$name]);
			} else {
				$this->protect[$name]--;
			}
		}
	}
}
