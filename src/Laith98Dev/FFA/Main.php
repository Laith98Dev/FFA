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

use Laith98Dev\FFA\commands\FFACommand;
use Laith98Dev\FFA\game\FFAGame;
use Laith98Dev\FFA\providers\DefaultProvider;
use Laith98Dev\FFA\tasks\ArenasTask;
use Laith98Dev\FFA\utils\SQLKeyStorer;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

use pocketmine\player\Player;
use pocketmine\player\GameMode;

use pocketmine\utils\{Config, TextFormat as TF};

class Main extends PluginBase implements Listener
{
	/** @var FFAGame[] */
	private array $arenas = [];

	private static $instance;

	private DefaultProvider $provider;

	private array $defaultData = [
		"scoreboardIp" => "play.example.net",
		"banned-commands" => ["/kill"],
		"death-respawn-inMap" => true,
		"join-and-respawn-protected" => true,
		"protected-time" => 3,
		"protected-message" => "&eYou're now protected for &c{TIME} &eseconds",
		"death-attack-message" => "&e{PLAYER} &fwas killed by &c{KILLER}",
		"death-void-message" => "&c{PLAYER} &ffall into void",
		"respawn-message" => "&eRespawned",
		"join-message" => "&7{PLAYER} &ejoined to game.",
		"leave-message" => "&7{PLAYER} &ehas leave to game.",
		"kits" => [
			"default" => [
				"slot-0" => [
					"id" => ItemIds::IRON_SWORD,
					"meta" => 0,
					"count" => 1,
					"enchants" => []
				],
				"slot-1" => [
					"id" => ItemIds::GOLDEN_APPLE,
					"meta" => 0,
					"count" => 5,
					"enchants" => []
				],
				"slot-2" => [
					"id" => ItemIds::BOW,
					"meta" => 0,
					"count" => 1,
					"enchants" => []
				],
				"slot-3" => [
					"id" => ItemIds::ARROW,
					"meta" => 0,
					"count" => 15,
					"enchants" => []
				],
				"helmet" => [
					"id" => ItemIds::IRON_HELMET,
					"enchants" => []
				],
				"chestplate" => [
					"id" => ItemIds::IRON_CHESTPLATE,
					"enchants" => [
						"id-" . EnchantmentIds::PROTECTION => [
							"level" => 2
						]
					]
				],
				"leggings" => [
					"id" => ItemIds::IRON_LEGGINGS,
					"enchants" => []
				],
				"boots" => [
					"id" => ItemIds::IRON_BOOTS,
					"enchants" => []
				]
			]
		],
		"kills-messages" => [
			"&eYou're the boss, you've got {KILLS} kills :).",
			"&eGood one you've now reached a {KILLS} kills :D.",
			"&eYou are now a great warrior, you've got {KILLS} kills ;D."
		],
		"scoreboard-title" => "FFA",
		"provider" => "sqlite",
		"database" => [
			"type" => "sqlite",
			"sqlite" => [
				"file" => "arenas.sql"
			],
			"mysql" => [
				"host" => "127.0.0.1",
				"username" => "root",
				"password" => "",
				"schema" => "your_schema"
			],
			"worker-limit" => 1
		]
	];

	private array $kits = [];

	public function onLoad(): void{
		self::$instance = $this;
	}

	public static function getInstaance(): Main{
		return self::$instance;
	}
	
	public function onEnable(): void{
		@mkdir($this->getDataFolder());
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->getScheduler()->scheduleRepeatingTask(new ArenasTask($this), 20);

		$this->getServer()->getCommandMap()->register($this->getName(), new FFACommand($this));
		
		$this->initConfig();
		$this->setProvider();
		$this->loadKits();
		$this->loadArenas();
	}
	
	public function initConfig(){
		if(!is_file($this->getDataFolder() . "config.yml")){
			(new Config($this->getDataFolder() . "config.yml", Config::YAML, $this->defaultData));
		} else {
			$cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
			$all = $cfg->getAll();
			foreach (array_keys($this->defaultData) as $key){
				if(!isset($all[$key])){
					rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config_old.yml");
					
					(new Config($this->getDataFolder() . "config.yml", Config::YAML, $this->defaultData));
					
					break;
				}
			}
		}
	}

	public function setProvider(){
		$prov = $this->getConfig()->get("provider");
		$provider = match ($prov){
			"sqlite" => new DefaultProvider($this),
			default => null
		};

		if($provider === null){
			$this->getLogger()->error("Invalid provider, expected 'sqlite', but got '" . strval($prov) . "'");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->provider = $provider;
	}

	public function loadKits(){
		$cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML, $this->defaultData);
		$kits = $cfg->get("kits", []);

		foreach ($kits as $name => $data){
			$items = [];
			$armors = [];

			foreach ($data as $slot_ => $slotData){
				if(strpos($slot_, "slot-") !== false){
					$slot = str_replace("slot-", "", $slot_);
					foreach (["id", "meta", "count", "enchants"] as $key){
						if(!isset($slotData[$key])){
							$this->getLogger()->error("Failed to load default kit, Error: Missing a required key of slot #" . $slot . " (" . $key . ")");
							$this->getServer()->getPluginManager()->disablePlugin($this);
							continue;
						}
					}

					$id = $slotData["id"] ?? 0;
					$meta = $slotData["meta"] ?? 0;
					$count = $slotData["count"] ?? 1;
					$enchants = $slotData["enchants"] ?? [];

					$item = ItemFactory::getInstance()->get($id, $meta, $count);

					if(count($enchants) > 0){
						foreach ($enchants as $id_ => $enchantData){
							$eId = str_replace("id-", "", $id_);
							if(!isset($enchantData["level"])){
								$this->getLogger()->error("Failed to load default kit, Error: Missing a required key of enchant for item " . $eId . " (level)");
								$this->getServer()->getPluginManager()->disablePlugin($this);
								continue;
							}

							$eLevel = $enchantData["level"];

							$enchant = new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(intval($eId)), $eLevel);
							$item->addEnchantment($enchant);
						}
					}

					if(!$item->isNull()){
						$items[$slot] = $item;
					}

					continue;
				}

				if(in_array($slot_, ["helmet", "chestplate", "leggings", "boots"])){
					foreach (["id", "enchants"] as $key){
						if(!isset($slotData[$key])){
							$this->getLogger()->error("Failed to load default kit, Error: Missing a required key of armor (" . $key . ")");
							$this->getServer()->getPluginManager()->disablePlugin($this);
							continue;
						}
					}

					$id = $slotData["id"];
					$enchants = $slotData["enchants"];

					$item = ItemFactory::getInstance()->get($id, 0, 1);

					if(count($enchants) > 0){
						foreach ($enchants as $id_ => $enchantData){
							$eId = str_replace("id-", "", $id_);
							if(!isset($enchantData["level"])){
								$this->getLogger()->error("Failed to load default kit, Error: Missing a required key of enchant id " . $eId . " (level)");
								$this->getServer()->getPluginManager()->disablePlugin($this);
								continue;
							}

							$eLevel = $enchantData["level"];

							$enchant = new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(intval($eId)), $eLevel);
							$item->addEnchantment($enchant);
						}
					}

					if(!$item->isNull()){
						$armors[$slot_] = $item;
					}
				}
			}

			$this->kits[$name]["items"] = $items;
			$this->kits[$name]["armors"] = $armors;
		}
	}
	
	public function loadArenas(){
		if($this->isDisabled()) return;
		$this->getProvider()->db()->executeSelect(SQLKeyStorer::GET_ARENAS,
		[],
		function(array $rows){
			if(count($rows) > 0){
				foreach ($rows as $data){
					if(!isset($data["name"]) || !isset($data["world"]) || !isset($data["lobby"]) || !isset($data["respawn"])){
						if(isset($data["name"]))
							$this->getLogger()->error("Error in load arena " . $data["name"] . " because corrupt data!");
						continue;
					}

					$this->getServer()->getWorldManager()->loadWorld($data["world"]);
					if(($world = $this->getServer()->getWorldManager()->getWorldByName($data["world"])) !== null){
						$world->setTime(1000);
						$world->stopTime();
					}

					$data["lobby"] = json_decode($data["lobby"], true);
					$data["respawn"] = json_decode($data["respawn"], true);

					$this->arenas[$data["name"]] = new FFAGame($this, $data);
				}
			}
		});
	}
	
	public function addArena(array $data, callable $callback){
		if(!isset($data["name"]) || !isset($data["world"]) || !isset($data["lobby"]) || !isset($data["respawn"])){
			$callback(true);
			return;
		}
		
		$name = $data["name"];
		$world = $data["world"];
		$lobby = $data["lobby"];
		$respawn = $data["respawn"];
		
		$this->getProvider()->db()->executeInsert(SQLKeyStorer::ADD_ARENA,
		[
			"name" => $name,
			"world" => $world,
			"lobby" => json_encode($lobby),
			"respawn" => json_encode($respawn)
		]);

		$callback(true);

		$this->arenas[$data["name"]] = new FFAGame($this, $data);
	}
	
	public function removeArena(string $name, callable $callback){
		$this->arena_Exist($name, function (bool $exists) use ($name, $callback){
			if($exists){
				$this->getProvider()->db()->executeChange(SQLKeyStorer::DELETE_ARENA,
				[
					"name" => $name
				]);

				if(($arena = $this->getArena($name)) !== null){
					foreach ($arena->getPlayers() as $player){
						$arena->quitPlayer($player);
					}
				}

				if(isset($this->arenas[$name]))
					unset($this->arenas[$name]);

				$callback(true);
			} else {
				$callback(false);
			}
		});
	}

	public function arena_Exist(string $name, callable $callback){
		$this->getProvider()->db()->executeSelect(SQLKeyStorer::GET_ARENAS,
		[],
		function(array $rows) use ($name, $callback) {
			if(count($rows) > 0){
				foreach ($rows as $arena){
					if(strtolower($arena["name"]) == strtolower($name)){
						$callback(true);
						return;
					}
				}
			}

			$callback(false);
		});
	}

	public function getKits(): array{
		return $this->kits;
	}
	
	public function getArenas(): array{
		return $this->arenas;
	}

	public function getProvider(): ?DefaultProvider{
		return $this->provider;
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
			if(($arena = $this->getPlayerArena($player)) !== null && $from->getWorld()->getFolderName() !== $to->getWorld()->getFolderName()){
				$arena->quitPlayer($player);
			}
		}
	}
	
	public function onPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if($player instanceof Player){
			if(($arena = $this->getPlayerArena($player)) !== null){
				// if(in_array($player->getGamemode(), [0, 2])){
				if($player->getGamemode()->equals(GameMode::SURVIVAL()) || $player->getGamemode()->equals(GameMode::ADVENTURE())){
					$event->cancel();
				}
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		if($player instanceof Player){
			if(($arena = $this->getPlayerArena($player)) !== null){
				// if(in_array($player->getGamemode(), [0, 2])){
				if($player->getGamemode()->equals(GameMode::SURVIVAL()) || $player->getGamemode()->equals(GameMode::ADVENTURE())){
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
	
	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$command = $event->getMessage();
		if($player instanceof Player){
			$cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
			$banned = $cfg->get("banned-commands", []);
			$banned = array_map("strtolower", $banned);
			if(($arena = $this->getPlayerArena($player)) !== null && in_array(strtolower(explode(" ", $command, 2)[0]), $banned)) {
				$player->sendMessage(TF::RED . "you cannot use this command here!");
				$event->cancel();
			}
		}
	}
	
	public function addKill(Player $player, int $add = 1){
		$this->getProvider()->db()->executeChange(SQLKeyStorer::ADD_KILLS,
		[
			"player" => $player->getName(),
			"kills" => $add
		]);
	}
	
	public function addKillByName(string $name, int $add = 1){
		$this->getProvider()->db()->executeChange(SQLKeyStorer::ADD_KILLS,
		[
			"player" => $name,
			"kills" => $add
		]);
	}
	
	public function addDeath(Player $player, int $add = 1){
		$this->getProvider()->db()->executeChange(SQLKeyStorer::ADD_DEATHS,
		[
			"player" => $player->getName(),
			"deaths" => $add
		]);
	}
	
	public function addDeathByName(string $name, int $add = 1){
		$this->getProvider()->db()->executeChange(SQLKeyStorer::ADD_DEATHS,
		[
			"player" => $name,
			"deaths" => $add
		]);
	}
	
	public function getKills(Player $player, callable $callback){
		$this->getProvider()->db()->executeSelect(SQLKeyStorer::GET_KILLS,
		[
			"player" => $player->getName()
		],
		function(array $rows) use ($callback) {
			if(isset($rows[0])){
				$callback($rows[0]["kills"]);
			} else {
				$callback(0);
			}
		});
	}
	
	public function getKillsByName(string $name, callable $callback){

		$this->getProvider()->db()->executeSelect(SQLKeyStorer::GET_KILLS,
		[
			"player" => $name
		],
		function(array $rows) use ($callback) {
			if(isset($rows[0])){
				$callback($rows[0]["kills"]);
			} else {
				$callback(0);
			}
		});
	}
	
	public function getDeaths(Player $player, callable $callback){
		$this->getProvider()->db()->executeSelect(SQLKeyStorer::GET_DEATHS,
		[
			"player" => $player->getName()
		],
		function(array $rows) use ($callback) {
			if(isset($rows[0])){
				$callback($rows[0]["deaths"]);
			} else {
				$callback(0);
			}
		});
	}
	
	public function getDeathsByName(string $name, callable $callback){
		$this->getProvider()->db()->executeSelect(SQLKeyStorer::GET_DEATHS,
		[
			"player" => $name
		],
		function(array $rows) use ($callback) {
			if(isset($rows[0])){
				$callback($rows[0]["deaths"]);
			} else {
				$callback(0);
			}
		});
	}
}
