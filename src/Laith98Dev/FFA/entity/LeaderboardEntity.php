<?php

declare(strict_types=1);

namespace Laith98Dev\FFA\entity;

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

use Laith98Dev\FFA\API;
use Laith98Dev\FFA\utils\ClosureResult;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class LeaderboardEntity extends Entity
{
    public const TAG_TYPE = "leaderboard_type";

    public const TOP_TYPE_KILLS = "kills";
    public const TOP_TYPE_DEATHS = "deaths";
    public const TOP_TYPE_KILLSTREAK = "killstreak";

    public static array $topsTypes = [
        self::TOP_TYPE_KILLS,
        self::TOP_TYPE_DEATHS,
        self::TOP_TYPE_KILLSTREAK,
    ];

    public string $type = "";

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CHICKEN;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1, 1);
    }

    protected function getInitialGravity(): float
    {
        return 0.0;
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0.0;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        if ($nbt->getTag(self::TAG_TYPE) === null) {
            $this->flagForDespawn();
            return;
        }

        $type = $nbt->getString(self::TAG_TYPE, "");

        if (!in_array($type, self::$topsTypes)) {
            $this->flagForDespawn();
            return;
        }

        $this->type = $type;
        $this->setHasGravity(false);
        $this->setCanSaveWithChunk(true);
        $this->setNoClientPredictions();

        $this->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
        $this->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
        $this->setScale(0.00000001);

        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();

        $this->setNameTag("Loading Data...");
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setString(self::TAG_TYPE, $this->type);
        return $nbt;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        // Update every minute
        if ($this->ticksLived % 1200 !== 0) {
            return parent::entityBaseTick($tickDiff);
        }

        API::getTops($this->type, 10, function (ClosureResult $result) {
            if (!$this->isAlive() || $this->isFlaggedForDespawn() || $this->isClosed()) {
                return;
            }

            if ($result->isSuccess()) {
                $data = $result->getValue();
                $text = "";

                if ($this->type === self::TOP_TYPE_KILLS) {
                    $text .= "§l§6Top Kills§r\n";
                } elseif ($this->type === self::TOP_TYPE_DEATHS) {
                    $text .= "§l§6Top Deaths§r\n";
                } else {
                    $text .= "§l§6Top Killstreak§r\n";
                }

                $text .= "§7" . str_repeat("━", 20) . "\n";

                $counter = 1;
                foreach ($data as $info) {
                    $rankColor = match ($counter) {
                        1 => "§6",
                        2 => "§7",
                        3 => "§c",
                        default => "§7"
                    };

                    $player = $info["player"] ?? "Unknown";
                    $value = $info["value"] ?? 0;

                    if (strlen($player) > 12) {
                        $player = substr($player, 0, 11) . "…";
                    }

                    $text .= $rankColor . "#" . $counter . " §8- §f" . $player . " §8- §e" . number_format($value) . "\n";
                    $counter++;
                }

                $text .= "§7" . str_repeat("━", 20);
                $this->setNameTag($text);
            }
        });

        return parent::entityBaseTick($tickDiff);
    }

    public function setMotion(Vector3 $motion): bool
    {
        return false;
    }

    public function tryChangeMovement(): void
    {
        return;
    }

    public function canBeMovedByCurrents(): bool
    {
        return false;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();
    }
}
