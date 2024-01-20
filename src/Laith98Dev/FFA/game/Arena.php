<?php

declare(strict_types=1);

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
use pocketmine\world\World;
use SOFe\AwaitGenerator\Await;

class Arena 
{
	private array $players = [];
	
	private array $scoreboards = [];
	
	private int $scoreboardsLine = 0;

	private array $protect = [];
	
	public function __construct(
		private array $data
	){
		// NOOP
	}
	
	public function getPlugin(): Main{
		return Main::getInstaance();
	}
	
	public function updateData(array $data): void{
		$this->data = $data;
	}
	
	public function getData(): array{
		return $this->data;
	}
	
	public function getName(): string{
		return $this->getData()["name"];
	}
	
	public function getWorldName(): string{
		return $this->getData()["world"];
	}
	
	public function getLobby(): array{
		return $this->getData()["lobby"];
	}
	
	public function getRespawn(): array{
		return $this->getData()["respawn"];
	}
	
	/**
	 * @return Player[]
	 */
	public function getPlayers(): array{
		return $this->players;
	}
	
	/**
	 * @param Player $player
	 * @return bool
	 */
	public function isProtected(Player $player): bool{
		return isset($this->protect[$player->getName()]);
	}

	public function getProtectTime(Player $player): int{
		return $this->protect[$player->getName()] ?? 0;
	}
	
	public function inArena(Player $player): bool{
		return isset($this->players[$player->getName()]) ? true : false;
	}
	
	public function new(Player $player, string $objectiveName, string $displayName): void{
		if(isset($this->scoreboards[$player->getName()])){
			$this->remove($player);
		}

		if($player->isConnected()) $player->getNetworkSession()->sendDataPacket(
			SetDisplayObjectivePacket::create(
				SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR,
				$objectiveName,
				$displayName,
				"dummy",
				SetDisplayObjectivePacket::SORT_ORDER_ASCENDING
			)
		);
		$this->scoreboards[$player->getName()] = $objectiveName;
	}

	public function remove(Player $player): void{
		$objectiveName = $this->getObjectiveName($player) ?? "ffa";
		if($player->isConnected()) $player->getNetworkSession()->sendDataPacket(
			RemoveObjectivePacket::create($objectiveName)
		);
		unset($this->scoreboards[$player->getName()]);
	}

	public function setLine(Player $player, int $score, string $message): void{
		if(!isset($this->scoreboards[$player->getName()])){
			$this->getPlugin()->getLogger()->error("You cannot set a score for a player with no scoreboard.");
			return;
		}

		if($score > 15 || $score < 1){
			$this->getPlugin()->getLogger()->error("Score must be between the value of 1-15. $score out of range");
			return;
		}

		$objectiveName = $this->getObjectiveName($player) ?? "ffa";

		$entry = new ScorePacketEntry();
		$entry->objectiveName = $objectiveName;
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $message;
		$entry->score = $score;
		$entry->scoreboardId = $score;

		if($player->isConnected()) $player->getNetworkSession()->sendDataPacket(SetScorePacket::create(
			SetScorePacket::TYPE_CHANGE,
			[$entry]
		));
	}

	public function getObjectiveName(Player $player): ?string{
		return isset($this->scoreboards[$player->getName()]) ? $this->scoreboards[$player->getName()] : null;
	}
	
	public function getWorld(?string $name = null): ?World{
		if($name === null){
			$this->getPlugin()->getServer()->getWorldManager()->loadWorld($this->getWorldName());
			return $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($this->getWorldName());
		}
		return $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($name);
	}
	
	public function broadcastMessage(string $message){
		foreach ($this->getPlayers() as $player){
			if($player->isConnected()) $player->sendMessage($message);
		}
	}
	
	public function joinPlayer(Player $player): bool{
		if(isset($this->players[$player->getName()])){
			return false;
		}

		$lobby = $this->getLobby();
		
		if(empty($lobby)){
			if($player->hasPermission("ffa.command.admin")){
				$player->sendMessage(TF::RED . "Please set lobby position. Usage: /ffa setlobby");
			}
			return false;
		}

		if(empty($this->getRespawn())){
			if($player->hasPermission("ffa.command.admin")){
				$player->sendMessage(TF::RED . "Please set respawn position. Usage: /ffa setrespawn");
			}
			return false;
		}
		
		$x = floatval($lobby["PX"]);
		$y = floatval($lobby["PY"]);
		$z = floatval($lobby["PZ"]);
		$yaw = floatval($lobby["YAW"]);
		$pitch = floatval($lobby["PITCH"]);
		
		$player->teleport(new Position($x, $y, $z, $this->getWorld()), $yaw, $pitch);
		
		$player->setGamemode(GameMode::ADVENTURE()); 
		$this->addItems($player);
		
		$this->players[$player->getName()] = $player;
		
		$this->broadcastMessage(Utils::messageFormat($this->getPlugin()->getConfig()->get("join-message"), $player, $this));
		
		if($this->getPlugin()->getConfig()->get("join-and-respawn-protected") === true){
			$this->protect[$player->getName()] = $this->getPlugin()->getConfig()->get("protected-time", 3);
			$player->sendMessage(Utils::messageFormat($this->getPlugin()->getConfig()->get("protected-message"), $player, $this));
		}

		return true;
	}
	
	public function quitPlayer(Player $player): bool{
		
		if(!isset($this->players[$player->getName()])){
			return false;
		}
		
		unset($this->players[$player->getName()]);
		
		$this->remove($player);
		
		$player->teleport($this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getOffHandInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();
		$player->getEffects()->clear();
		$player->setGamemode($this->getPlugin()->getServer()->getGamemode());
		$player->setHealth($player->getMaxHealth());
		$player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
		
		$this->broadcastMessage(Utils::messageFormat($this->getPlugin()->getConfig()->get("leave-message"), $player, $this));
		return true;
	}
	
	public function killPlayer(Player $player): void{
		$message = null;
		$event = $player->getLastDamageCause();
		
		if($event === null){
			return;
		}

		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getOffHandInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();
		$player->getEffects()->clear();
		
		$player->setGamemode(GameMode::ADVENTURE());
		$player->setHealth($player->getMaxHealth());
		$player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());

		Await::f2c(function () use ($player, $event, $message): Generator{
			yield from Await::promise(
				fn(Closure $resolve) => API::addDeath($player, 1, $resolve)
			);

			switch ($event->getCause()){
				case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
					$damager = $event instanceof EntityDamageByEntityEvent ? $event->getDamager() : null;
					if($damager !== null && $damager instanceof Player){
						$message = str_replace(["{PLAYER}", "{KILLER}", "&"], [$player->getName(), $damager->getName(), TF::ESCAPE], $this->getPlugin()->getConfig()->get("death-attack-message"));

						yield from Await::promise(
							fn(Closure $resolve) => API::addKill($damager, 1, $resolve)
						);

						$kills = yield from Await::promise(
							fn(Closure $resolve) => API::getKills($damager, fn(ClosureResult $response) => $resolve($response->getValue())) 
						);

						if($kills % 5 === 0){
							$messages = $this->getPlugin()->getConfig()->get("kills-messages", []);
							if(!empty($messages)){
								$killMsg = $messages[array_rand($messages)];
								$killMsg = Utils::messageFormat($killMsg, $damager, $this);
								$killMsg = str_replace("{KILLS}", strval($kills), $killMsg);
								$damager->sendMessage($killMsg);
							}
						}
						
						$damager->setHealth($damager->getMaxHealth());
						$damager->sendPopup(TF::YELLOW . "+1 Kill");
						$this->addItems($damager);
					}
				break;
				
				case EntityDamageEvent::CAUSE_VOID:
					$message = str_replace(["{PLAYER}", "&"], [$player->getName(), TF::ESCAPE], $this->getPlugin()->getConfig()->get("death-void-message"));
				break;
			}
			
			if($message !== null){
				$this->broadcastMessage($message);
			}

			if($this->getPlugin()->getConfig()->get("death-respawn-inMap") === true){
				$this->respawn($player);
			} else {
				$this->quitPlayer($player);
			}
		});
	}
	
	public function respawn(Player $player){
		$player->setGamemode(GameMode::ADVENTURE());
		$player->setHealth($player->getMaxHealth());
		$player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());

		$this->addItems($player);
		
		$respawn = $this->getRespawn();
		$x = floatval($respawn["PX"]);
		$y = floatval($respawn["PY"]);
		$z = floatval($respawn["PZ"]);
		$yaw = floatval($respawn["YAW"]);
		$pitch = floatval($respawn["PITCH"]);
		
		$player->teleport(new Position($x, $y, $z, $this->getWorld()), $yaw, $pitch);
		
		if($this->getPlugin()->getConfig()->get("join-and-respawn-protected") === true){
			$this->protect[$player->getName()] = $this->getPlugin()->getConfig()->get("protected-time", 3);
			$player->sendMessage(str_replace(["{PLAYER}", "{TIME}", "&"], [$player->getName(), strval($this->protect[$player->getName()]), TF::ESCAPE], $this->getPlugin()->getConfig()->get("protected-message")));
		}
		
		$player->sendTitle(Utils::messageFormat($this->getPlugin()->getConfig()->get("respawn-message"), $player, $this));
	}

	private function addItems(Player $player){
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getOffHandInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();
		$player->getEffects()->clear();
		
		$defaultKit = $this->getPlugin()->getKits()["default"];

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
			if(!$player->isConnected()) continue;

			API::getKills($player, function (ClosureResult $response) use ($player){
				$kills = $response->getValue();
				API::getDeaths($player, function (ClosureResult $response) use ($player, $kills){
					$deaths = $response->getValue();

					$this->new($player, "ffa", Main::$scoreboard_lines[$this->scoreboardsLine]);
					$this->setLine($player, 1, " ");
					$this->setLine($player, 2, " Players: " . TF::YELLOW . count($this->getPlayers()) . "  ");
					$this->setLine($player, 3, "  ");
					$this->setLine($player, 4, " Map: " . TF::YELLOW . $this->getName() . "  ");
					$this->setLine($player, 5, "   ");
					$this->setLine($player, 6, " Kills: " . TF::YELLOW . $kills . " ");
					$this->setLine($player, 7, " Deaths: " . TF::YELLOW . $deaths . " ");
					$this->setLine($player, 8, "    ");
					$this->setLine($player, 9, " " . str_replace("&", TF::ESCAPE, $this->getPlugin()->getConfig()->get("scoreboardIp", "play.example.net") . " "));
				});
			});
		}
		
		if($this->scoreboardsLine == (count(Main::$scoreboard_lines) - 1)){
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