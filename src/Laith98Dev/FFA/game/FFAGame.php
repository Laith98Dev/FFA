<?php

namespace Laith98Dev\FFA\game;

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

use Laith98Dev\FFA\Main;
use Laith98Dev\FFA\utils\Utils;

use pocketmine\player\Player;
use pocketmine\player\GameMode;

use pocketmine\world\Position;

use pocketmine\utils\TextFormat as TF;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;

class FFAGame 
{
	private array $players = [];
	
	private array $scoreboards = [];
	
	private int $scoreboardsLine = 0;
	
	private array $scoreboardsLines = [
		0 => TF::BOLD . TF::YELLOW . "FFA",
		1 => TF::BOLD . TF::WHITE . "F" . TF::YELLOW . "FA",
		2 => TF::BOLD . TF::YELLOW . "F" . TF::WHITE . "F" . TF::YELLOW . "A",
		3 => TF::BOLD . TF::YELLOW . "FF" . TF::WHITE . "A",
		4 => TF::BOLD . TF::WHITE . "FFA"
	];
	
	private array $protect = [];
	
	public function __construct(
		private Main $plugin,
		private array $data
		){
		$this->setScoreTitle();
	}
	
	public function getPlugin(){
		return $this->plugin;
	}

	public function setScoreTitle(){
		$index = [];
		$title = $this->plugin->getConfig()->get("scoreboard-title", "FFA");

		$index[] = TF::BOLD . TF::YELLOW . $title;
		$v = 0;
		for ($i = 0; $i < strlen($title); $i++){
			$final = "";
			for($i_ = 0; $i_ < strlen($title); $i_++){
				if($i_ == $v){
					$final .= TF::BOLD . TF::WHITE . $title[$i_];
				} else {
					$final .= TF::BOLD . TF::YELLOW . $title[$i_];
				}
			}
			$index[] = $final;
			$v++;
		}

		$index[] = TF::BOLD . TF::WHITE . $title;
		$this->scoreboardsLines = $index;
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

	public function getProtectTime(Player $player){
		return $this->protect[$player->getName()] ?? 0;
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
		if($player->isOnline()) $player->getNetworkSession()->sendDataPacket($pk);
		$this->scoreboards[$player->getName()] = $objectiveName;
	}

	public function remove(Player $player): void{
		$objectiveName = $this->getObjectiveName($player) ?? "ffa";
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objectiveName;
		if($player->isOnline()) $player->getNetworkSession()->sendDataPacket($pk);
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
		if($player->isOnline()) $player->getNetworkSession()->sendDataPacket($pk);
	}

	public function getObjectiveName(Player $player): ?string{
		return isset($this->scoreboards[$player->getName()]) ? $this->scoreboards[$player->getName()] : null;
	}
	
	public function getLevel(?string $name = null){
		if($name == null){
			$this->plugin->getServer()->getWorldManager()->loadWorld($this->getWorld());
			return $this->plugin->getServer()->getWorldManager()->getWorldByName($this->getWorld());
		}
		return $this->plugin->getServer()->getWorldManager()->getWorldByName($name);
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
		
		$x = floatval($lobby["PX"]);
		$y = floatval($lobby["PY"]);
		$z = floatval($lobby["PZ"]);
		$yaw = floatval($lobby["YAW"]);
		$pitch = floatval($lobby["PITCH"]);
		
		$player->teleport(new Position($x, $y, $z, $this->getLevel()), $yaw, $pitch);
		
		$player->setGamemode(GameMode::ADVENTURE());
		$this->addItems($player);
		
		$this->players[$player->getName()] = $player;
		
		$this->broadcast(Utils::messageFormat($this->getPlugin()->getConfig()->get("join-message"), $player, $this));
		
		if($this->plugin->getConfig()->get("join-and-respawn-protected") === true){
			$this->protect[$player->getName()] = $this->plugin->getConfig()->get("protected-time", 3);
			$player->sendMessage(Utils::messageFormat($this->getPlugin()->getConfig()->get("protected-message"), $player, $this));
		}
	
		return true;
	}
	
	public function quitPlayer(Player $player): bool{
		
		if(!isset($this->players[$player->getName()]))
			return false;
		
		unset($this->players[$player->getName()]);
		
		$this->remove($player);
		
		$player->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();
		$player->getEffects()->clear();
		$player->setGamemode($this->plugin->getServer()->getGamemode());
		$player->setHealth(20);
		$player->getHungerManager()->setFood(20);
		
		$this->broadcast(Utils::messageFormat($this->getPlugin()->getConfig()->get("leave-message"), $player, $this));
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
		$player->getEffects()->clear();
		
		$player->setGamemode(GameMode::ADVENTURE());
		$player->setHealth(20);
		$player->getHungerManager()->setFood(20);
		$this->plugin->addDeath($player);
		switch ($event->getCause()){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				$damager = $event instanceof EntityDamageByEntityEvent ? $event->getDamager() : null;
				if($damager !== null && $damager instanceof Player){
					$message = str_replace(["{PLAYER}", "{KILLER}", "&"], [$player->getName(), $damager->getName(), TF::ESCAPE], $this->plugin->getConfig()->get("death-attack-message"));
					$this->plugin->addKill($damager);

					$this->plugin->getKills($damager, function ($kills) use ($damager){
						if($kills % 5 === 0){
							$messages = $this->plugin->getConfig()->get("kills-messages", []);
							if(count($messages) > 0){
								$killMsg = $messages[array_rand($messages)];
								$killMsg = Utils::messageFormat($killMsg, $damager, $this);
								$killMsg = str_replace("{KILLS}", $kills, $killMsg);
								$damager->sendMessage($killMsg);
							}
						}
					});
					
					$damager->sendPopup(TF::YELLOW . "+1 Kill");
					$this->addItems($damager);
				}
			break;
			
			case EntityDamageEvent::CAUSE_VOID:
				$message = str_replace(["{PLAYER}", "&"], [$player->getName(), TF::ESCAPE], $this->plugin->getConfig()->get("death-void-message"));
			break;
		}
		
		if($message !== null)
			$this->broadcast($message);
		
		if($this->plugin->getConfig()->get("death-respawn-inMap") === true){
			$this->respawn($player);
		} else {
			$this->quitPlayer($player);
		}
	}
	
	public function respawn(Player $player){
		$player->setGamemode(GameMode::ADVENTURE());
		
		$this->addItems($player);
		
		$respawn = $this->getRespawn();
		$x = floatval($respawn["PX"]);
		$y = floatval($respawn["PY"]);
		$z = floatval($respawn["PZ"]);
		$yaw = floatval($respawn["YAW"]);
		$pitch = floatval($respawn["PITCH"]);
		
		$player->teleport(new Position($x, $y, $z, $this->getLevel()), $yaw, $pitch);
		
		if($this->plugin->getConfig()->get("join-and-respawn-protected") === true){
			$this->protect[$player->getName()] = $this->plugin->getConfig()->get("protected-time", 3);
			$player->sendMessage(str_replace(["{PLAYER}", "{TIME}", "&"], [$player->getName(), $this->protect[$player->getName()], TF::ESCAPE], $this->plugin->getConfig()->get("protected-message")));
		}
		
		$player->sendTitle(Utils::messageFormat($this->getPlugin()->getConfig()->get("respawn-message"), $player, $this));
	}

	private function addItems(Player $player){
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();
		$player->getEffects()->clear();
		
		// $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(ItemIds::IRON_SWORD, 0, 1));
		// $player->getInventory()->setItem(1, ItemFactory::getInstance()->get(ItemIds::GOLDEN_APPLE, 0, 5));
		// $player->getInventory()->setItem(2, ItemFactory::getInstance()->get(ItemIds::BOW, 0, 1));
		// $player->getInventory()->setItem(3, ItemFactory::getInstance()->get(ItemIds::ARROW, 0, 15));
		
		// $player->getArmorInventory()->setHelmet(ItemFactory::getInstance()->get(ItemIds::IRON_HELMET));
		// $player->getArmorInventory()->setChestplate(ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE));
		// $player->getArmorInventory()->setLeggings(ItemFactory::getInstance()->get(ItemIds::IRON_LEGGINGS));
		// $player->getArmorInventory()->setBoots(ItemFactory::getInstance()->get(ItemIds::IRON_BOOTS));

		$defaultKit = $this->plugin->getKits()["default"];
		$items = $defaultKit["items"];
		$armors = $defaultKit["armors"];

		foreach ($items as $slot => $item){
			$player->getInventory()->setItem(intval($slot), $item);
		}
		
		foreach ($armors as $type => $item){
			switch ($type){
				case "helmet":
					$player->getArmorInventory()->setHelmet($item);
					break;
				case "chestplate":
					$player->getArmorInventory()->setChestplate($item);
					break;
				case "leggings":
					$player->getArmorInventory()->setLeggings($item);
					break;
				case "boots":
					$player->getArmorInventory()->setBoots($item);
					break;
				
			}
		}
	}
	
	public function tick(){
		foreach ($this->getPlayers() as $player){
			if(!$player->isOnline()) continue;
			$this->getPlugin()->getKills($player, function($kills) use ($player): void{
				$this->getPlugin()->getDeaths($player, function($deaths) use ($player, $kills): void{
					$this->new($player, "ffa", $this->scoreboardsLines[$this->scoreboardsLine]);
					$this->setLine($player, 1, " ");
					$this->setLine($player, 2, " Players: " . TF::YELLOW . count($this->getPlayers()) . "  ");
					$this->setLine($player, 3, "  ");
					$this->setLine($player, 4, " Map: " . TF::YELLOW . $this->getName() . "  ");
					$this->setLine($player, 5, "   ");
					$this->setLine($player, 6, " Kills: " . TF::YELLOW . $kills . " ");
					$this->setLine($player, 7, " Deaths: " . TF::YELLOW . $deaths . " ");
					$this->setLine($player, 8, "    ");
					$this->setLine($player, 9, " " . str_replace("&", TF::ESCAPE, $this->plugin->getConfig()->get("scoreboardIp", "play.example.net") . " "));
				});
			});
		}
		
		if($this->scoreboardsLine == (count($this->scoreboardsLines) - 1)){
			$this->scoreboardsLine = 0;
		} else {
			++$this->scoreboardsLine;
		}
		
		foreach ($this->protect as $name => $time){
			if($time == 0){
				unset($this->protect[$name]);
			} else {
				$this->protect[$name]--;
			}
		}
	}
}
