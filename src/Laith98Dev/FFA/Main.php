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

use Closure;
use Exception;
use Generator;
use Laith98Dev\BanCommands\Main as BanCommands;
use Laith98Dev\FFA\commands\FFACommand;
use Laith98Dev\FFA\entity\LeaderboardEntity;
use Laith98Dev\FFA\game\Arena;
use Laith98Dev\FFA\providers\DefaultProvider;
use Laith98Dev\FFA\tasks\ArenasTask;
use Laith98Dev\FFA\utils\ClosureResult;
use Laith98Dev\FFA\utils\SQLKeyStorer;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

use pocketmine\utils\{Config, SingletonTrait, TextFormat as TF};
use pocketmine\world\World;
use poggit\libasynql\SqlError;
use SOFe\AwaitGenerator\Await;

class Main extends PluginBase implements Listener
{
	use SingletonTrait {
		reset as private;
		setInstance as private;
	}

	/** @var Arena[] */
	private array $arenas = [];

	private DefaultProvider $provider;

	private BanCommands $banCommands;

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
					"id" => "iron_sword",
					"count" => 1,
					"enchants" => []
				],
				"slot-1" => [
					"id" => "golden_apple",
					"count" => 5,
					"enchants" => []
				],
				"slot-2" => [
					"id" => "bow",
					"count" => 1,
					"enchants" => []
				],
				"slot-3" => [
					"id" => "arrow",
					"count" => 15,
					"enchants" => []
				],
				"helmet" => [
					"id" => "iron_helmet",
					"enchants" => []
				],
				"chestplate" => [
					"id" => "iron_chestplate",
					"enchants" => [
						"id-" . EnchantmentIds::PROTECTION => [
							"level" => 2
						]
					]
				],
				"leggings" => [
					"id" => "iron_leggings",
					"enchants" => []
				],
				"boots" => [
					"id" => "iron_boots",
					"enchants" => []
				]
			]
		],
		"kills-messages" => [
			"&eYou're the boss, you've got {KILLS} kills :).",
			"&eGood one, you've now reached {KILLS} kills :D.",
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

	public static array $scoreboard_lines = [];

	public function onLoad(): void
	{
		self::setInstance($this);
	}

	public function onEnable(): void
	{
		@mkdir($this->getDataFolder());

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->getScheduler()->scheduleRepeatingTask(new ArenasTask($this), 20);

		$this->getServer()->getCommandMap()->register($this->getName(), new FFACommand($this));

		$this->banCommands = $this->getServer()->getPluginManager()->getPlugin("BanCommands");

		$this->initConfig();
		$this->setProvider();
		$this->loadKits();
		$this->loadArenas();
		$this->loadBannedCommands();
		$this->registerEntities();
		$this->setScoreTitle();
	}

	/**
	 * @return BanCommands
	 */
	public function getBanManager(): BanCommands
	{
		return $this->banCommands;
	}

	/**
	 * @return void
	 */
	private function initConfig(): void
	{
		if (!is_file($this->getDataFolder() . "config.yml")) {
			(new Config($this->getDataFolder() . "config.yml", Config::YAML, $this->defaultData));
		} else {
			$cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
			$all = $cfg->getAll();
			foreach (array_keys($this->defaultData) as $key) {
				if (!isset($all[$key])) {
					rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config_old.yml");

					(new Config($this->getDataFolder() . "config.yml", Config::YAML, $this->defaultData));

					break;
				}
			}
		}
	}

	private function setScoreTitle(): void
	{
		$index = [];
		$title = $this->getConfig()->get("scoreboard-title", "FFA");

		$index[] = TF::BOLD . TF::YELLOW . $title;
		$v = 0;
		for ($i = 0; $i < strlen($title); $i++) {
			$final = "";
			for ($i_ = 0; $i_ < strlen($title); $i_++) {
				if ($i_ == $v) {
					$final .= TF::BOLD . TF::WHITE . $title[$i_];
				} else {
					$final .= TF::BOLD . TF::YELLOW . $title[$i_];
				}
			}
			$index[] = $final;
			$v++;
		}

		$index[] = TF::BOLD . TF::WHITE . $title;
		self::$scoreboard_lines = $index;
	}

	/**
	 * @return void
	 */
	private function setProvider(): void
	{
		$prov = $this->getConfig()->get("provider");
		$provider = match (strtolower($prov)) {
			"sqlite" => new DefaultProvider($this),
			default => null
		};

		if ($provider === null) {
			$this->getLogger()->error("Invalid provider, expected 'sqlite', but got '" . strval($prov) . "'");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->provider = $provider;
	}

	/**
	 * @internal This function could change anytime without warning.
	 * @return void
	 */
	public function loadKits(): void
	{
		$cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML, $this->defaultData);
		$kits = $cfg->get("kits", []);

		foreach ($kits as $name => $data) {
			$items = [];
			$armors = [];

			foreach ($data as $slot_ => $slotData) {
				if (str_starts_with($slot_, "slot-") !== false) {
					$slot = str_replace("slot-", "", $slot_);
					foreach (["id", "count", "enchants"] as $key) {
						if (!isset($slotData[$key])) {
							$this->getLogger()->error("Failed to load default kit, Error: Missing a required key of slot #" . $slot . " (" . $key . ")");
							$this->getServer()->getPluginManager()->disablePlugin($this);
							continue;
						}
					}

					$id = strval($slotData["id"] ?? 0);
					$count = $slotData["count"] ?? 1;
					$enchants = $slotData["enchants"] ?? [];

					try {
						$item = StringToItemParser::getInstance()->parse($id) ?? LegacyStringToItemParser::getInstance()->parse($id);
						$item->setCount($count);
					} catch (Exception) {
						$this->getLogger()->error("'" . $name . "' kit has an invalid item identifier: '" . $id . "'");
						continue;
					}

					if ($item->isNull()) {
						continue;
					}

					if (count($enchants) > 0) {
						foreach ($enchants as $id_ => $enchantData) {
							$eId = str_replace("id-", "", $id_);
							if (!isset($enchantData["level"])) {
								$this->getLogger()->error("Failed to load '" . $name . "' kit, Error: Missing a required key of enchant for item " . $eId . " (level)");
								$this->getServer()->getPluginManager()->disablePlugin($this);
								continue;
							}

							$eLevel = intval($enchantData["level"]);

							try {
								$enchantment = EnchantmentIdMap::getInstance()->fromId(intval($eId)) ?? StringToEnchantmentParser::getInstance()->parse($eId);
							} catch (Exception) {
								continue;
							}

							$item->addEnchantment(new EnchantmentInstance($enchantment, $eLevel));
						}
					}

					$items[$slot] = $item;
					continue;
				}

				if (in_array($slot_, ["helmet", "chestplate", "leggings", "boots"])) {
					foreach (["id", "enchants"] as $key) {
						if (!isset($slotData[$key])) {
							$this->getLogger()->error("Failed to load '" . $name . "' kit, Error: Missing a required key of armor (" . $key . ")");
							$this->getServer()->getPluginManager()->disablePlugin($this);
							continue;
						}
					}

					$id = strval($slotData["id"]);
					$enchants = $slotData["enchants"];

					try {
						$item = StringToItemParser::getInstance()->parse($id) ?? LegacyStringToItemParser::getInstance()->parse($id);
					} catch (Exception) {
						$this->getLogger()->error("'" . $name . "' kit has an invalid item identifier: '" . $id . "'");
						continue;
					}

					if ($item->isNull()) {
						continue;
					}

					if (!empty($enchants)) {
						foreach ($enchants as $id_ => $enchantData) {
							$eId = str_replace("id-", "", $id_);
							if (!isset($enchantData["level"])) {
								$this->getLogger()->error("Failed to load '" . $name . "' kit, Error: Missing a required key of enchant id " . $eId . " (level)");
								$this->getServer()->getPluginManager()->disablePlugin($this);
								continue;
							}

							$eLevel = intval($enchantData["level"]);

							try {
								$enchantment = EnchantmentIdMap::getInstance()->fromId(intval($eId)) ?? StringToEnchantmentParser::getInstance()->parse($eId);
							} catch (Exception) {
								continue;
							}

							$item->addEnchantment(new EnchantmentInstance($enchantment, $eLevel));
						}
					}

					$armors[$slot_] = $item;
				}
			}

			$this->kits[$name]["items"] = $items;
			$this->kits[$name]["armors"] = $armors;
		}
	}

	/**
	 * @return void
	 */
	private function loadBannedCommands(): void
	{
		$cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$commands = array_map("strtolower", $cfg->get("banned-commands", []));
		foreach ($commands as $cmd) {
			if (!$this->getBanManager()->addCommand($cmd)) {
				$this->getLogger()->info("Failed to ban the '" . $cmd . "' command; it's already banned.");
			}
		}
	}

	private function registerEntities(): void
	{
		EntityFactory::getInstance()->register(
			LeaderboardEntity::class,
			function (World $world, CompoundTag $nbt): LeaderboardEntity {
				return new LeaderboardEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
			},
			[LeaderboardEntity::class, "LeaderboardEntity"]
		);
	}

	/**
	 * @internal This function could change anytime without warning.
	 * @return void
	 */
	public function loadArenas(): void
	{
		if ($this->isDisabled()) return;

		Await::f2c(function (): Generator {
			$rows = yield from Await::promise(
				fn(Closure $resolve) => $this->getProvider()->db()->executeSelect(
					SQLKeyStorer::GET_ARENAS,
					[],
					$resolve,
					fn(SqlError $err) => $this->getLogger()->error("Failed to load the arenas; error: " . $err->getMessage())
				)
			);

			if (!empty($rows)) {
				foreach ($rows as $data) {
					if (!isset($data["name"], $data["world"], $data["lobby"], $data["respawn"], $data["kit"])) {
						if (isset($data["name"])) {
							$this->getLogger()->error("Failed to load arena '" . $data["name"] . "' because of corrupt data.");
						}
						continue;
					}

					if (!isset($this->kits[$data["kit"]])) {
						$this->getLogger()->error("Failed to load arena '" . $data["name"] . "' - unknown kit: '" . $data["kit"] . "'");
						continue;
					}

					$this->getServer()->getWorldManager()->loadWorld($data["world"]);
					if (($world = $this->getServer()->getWorldManager()->getWorldByName($data["world"])) === null) {
						$this->getLogger()->error("There is an error with loading the world '" . $data["world"] . "' of the arena '" . $data["name"] . "'.");
						continue;
					}

					$world->setTime(1000);
					$world->stopTime();

					$data["lobby"] = json_decode($data["lobby"], true);
					$data["respawn"] = json_decode($data["respawn"], true);

					$this->arenas[$data["name"]] = new Arena($data);
				}
			}
		});
	}

	public function addArena(array $data, Closure $onSuccess): void
	{
		Await::f2c(function () use ($data, $onSuccess): Generator {
			if (!isset($data["name"], $data["world"], $data["lobby"], $data["respawn"], $data["kit"])) {
				$onSuccess(
					ClosureResult::create(
						ClosureResult::STATE_FAILURE,
						true
					)
				);
				return;
			}

			$name = $data["name"];
			$world = $data["world"];
			$lobby = $data["lobby"];
			$respawn = $data["respawn"];
			$kit = $data["kit"];

			yield from Await::promise(
				fn(Closure $resolve) => $this->getProvider()->db()->executeInsert(
					SQLKeyStorer::ADD_ARENA,
					[
						"name" => $name,
						"world" => $world,
						"lobby" => json_encode($lobby),
						"respawn" => json_encode($respawn),
						"kit" => $kit
					],
					$resolve,
					fn(SqlError $err) => $onSuccess(
						ClosureResult::create(
							ClosureResult::STATE_FAILURE,
							$err->getMessage()
						)
					)
				)
			);

			$onSuccess(
				ClosureResult::create(
					ClosureResult::STATE_SUCCESS,
					true
				)
			);

			$this->arenas[$data["name"]] = new Arena($data);
		});
	}

	/**
	 * @param string	$name
	 * @param Closure	$onSuccess
	 * @return void
	 */
	public function removeArena(string $name, Closure $onSuccess): void
	{
		Await::f2c(function () use ($name, $onSuccess): Generator {
			/**
			 * @var ClosureResult $isValid
			 */
			$isValid = yield from Await::promise(
				fn(Closure $resolve) => API::isValidArena($name, $resolve)
			);

			if ($isValid->getValue()) {
				if (($arena = $this->getArena($name)) !== null) {
					foreach ($arena->getPlayers() as $player) {
						$arena->quitPlayer($player);
					}
				}

				if (isset($this->arenas[$name])) {
					unset($this->arenas[$name]);
				}

				$onSuccess(
					ClosureResult::create(
						ClosureResult::STATE_SUCCESS,
						true
					)
				);
			} else {
				$onSuccess(
					ClosureResult::create(
						ClosureResult::STATE_FAILURE,
						false
					)
				);
			}
		});
	}

	/**
	 * @return array<string, array<string, array>>
	 */
	public function getKits(): array
	{
		return $this->kits;
	}

	/**
	 * @return array<string, Arena>
	 */
	public function getArenas(): array
	{
		return $this->arenas;
	}

	/**
	 * @return DefaultProvider|null
	 */
	public function getProvider(): ?DefaultProvider
	{
		return $this->provider;
	}

	/**
	 * @param string $name
	 * @return Arena|null
	 */
	public function getArena(string $name): ?Arena
	{
		return isset($this->arenas[$name]) ? $this->arenas[$name] : null;
	}

	/**
	 * @param Player $player
	 * @param string $name
	 * @return bool
	 */
	public function joinArena(Player $player, string $name): bool
	{
		if (($arena = $this->getArena($name)) == null) {
			$player->sendMessage(TF::RED . "Arena not found!");
			return false;
		}

		if ($this->getPlayerArena($player) !== null) {
			$player->sendMessage(TF::RED . "You're already in the arena!");
			return false;
		}

		return $arena->joinPlayer($player);
	}

	/**
	 * @param Player $player
	 * @return bool
	 */
	public function joinRandomArena(Player $player): bool
	{
		if ($this->getPlayerArena($player) !== null) {
			$player->sendMessage(TF::RED . "You're already in the arena!");
			return false;
		}

		if (empty($this->getArenas())) {
			$player->sendMessage(TF::RED . "No arenas were found!");
			return false;
		}

		$all = [];
		foreach ($this->getArenas() as $arena) {
			$all[$arena->getName()] = count($arena->getPlayers());
		}

		arsort($all);

		foreach ($all as $arena_name => $players_count) {
			if ($this->joinArena($player, $arena_name)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Player $player
	 * @return Arena|null
	 */
	public function getPlayerArena(Player $player): ?Arena
	{
		foreach ($this->getArenas() as $arena) {
			if ($arena->inArena($player)) {
				return $arena;
			}
		}

		return null;
	}

	/**
	 * @param PlayerDropItemEvent $event
	 * @return void
	 */
	public function onDrop(PlayerDropItemEvent $event): void
	{
		if ($this->getPlayerArena($event->getPlayer()) !== null) {
			$event->cancel();
		}
	}

	/**
	 * @param PlayerExhaustEvent $event
	 * @return void
	 */
	public function onHunger(PlayerExhaustEvent $event): void
	{
		if ($this->getPlayerArena($event->getPlayer()) !== null) {
			$event->cancel();
		}
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @return void
	 */
	public function onQuit(PlayerQuitEvent $event): void
	{
		if (($arena = $this->getPlayerArena($event->getPlayer())) !== null) {
			$arena->quitPlayer($event->getPlayer());
		}
	}

	/**
	 * @param EntityTeleportEvent $event
	 * @return void
	 */
	public function onLevelChange(EntityTeleportEvent $event): void
	{
		$player = $event->getEntity();
		$from = $event->getFrom();
		$to = $event->getTo();
		if ($player instanceof Player) {
			if (($arena = $this->getPlayerArena($player)) !== null && $from->getWorld()->getFolderName() !== $to->getWorld()->getFolderName()) {
				$arena->quitPlayer($player);
			}
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 * @return void
	 */
	public function onPlace(BlockPlaceEvent $event): void
	{
		$player = $event->getPlayer();
		if ($this->getPlayerArena($player) !== null) {
			if (!$player->isCreative()) {
				$event->cancel();
			}
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 * @return void
	 */
	public function onBreak(BlockBreakEvent $event): void
	{
		$player = $event->getPlayer();
		if ($this->getPlayerArena($player) !== null) {
			if (!$player->isCreative()) {
				$event->cancel();
			}
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 * @return void
	 */
	public function onDamage(EntityDamageEvent $event): void
	{
		$entity = $event->getEntity();
		if ($entity instanceof Player) {
			if (($arena = $this->getPlayerArena($entity)) !== null) {
				if ($event->getCause() == EntityDamageEvent::CAUSE_FALL) {
					$event->cancel();
					return;
				}

				if ($entity->getHealth() <= $event->getFinalDamage()) {
					$arena->killPlayer($entity);
					$event->cancel();
				}
			}
		}
	}

	/**
	 * @param EntityDamageByEntityEvent $event
	 * @return void
	 */
	public function onDamageByEntity(EntityDamageByEntityEvent $event): void
	{
		$entity = $event->getEntity();
		$damager = $event->getDamager();

		if ($entity instanceof Player && $damager instanceof Player) {
			if (($arena = $this->getPlayerArena($entity)) !== null) {
				if ($arena->isProtected($entity) || $arena->isProtected($damager)) {
					$event->cancel();
				}
			}
		}
	}

	public function onEntityMotion(EntityMotionEvent $event): void
	{
		if ($event->getEntity() instanceof LeaderboardEntity) {
			$event->cancel();
		}
	}
}
