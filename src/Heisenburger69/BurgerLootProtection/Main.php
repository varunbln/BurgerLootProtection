<?php

declare(strict_types=1);

namespace Heisenburger69\BurgerLootProtection;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use JackMD\UpdateNotifier\UpdateNotifier;
use function in_array;

class Main extends PluginBase
{

    /**
     * @var Main
     */
    public static $instance;

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        Entity::registerEntity(ProtectedItemEntity::class, true, ["protectedItemEntity"]);
        $this->saveDefaultConfig();
        self::$instance = $this;
        UpdateNotifier::checkUpdate($this, $this->getDescription()->getName(), $this->getDescription()->getVersion());
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function checkProtectionPerms(Player $player): bool
    {
        if(!$this->getConfig()->get("enable-permission")) return true;
        if($player->hasPermission("burgerlootprotection.use")) return true;
        return false;
    }

    /**
     * @param Level $level
     * @return bool
     */
    public function checkProtectionLevel(Level $level): bool
    {
        $blacklist = $this->getConfig()->get("enable-world-blacklist");
        $whitelist = $this->getConfig()->get("enable-world-whitelist");
        $levelName = $level->getName();
        
        if($blacklist === $whitelist) return true;

        if($blacklist) {
            $disallowedWorlds = $this->getConfig()->get("blacklisted-worlds");
            if(in_array($levelName, $disallowedWorlds)) return false;
            return true;
        }
        
        if($whitelist) {
            $allowedWorlds = $this->getConfig()->get("whitelisted-worlds");
            if(in_array($levelName, $allowedWorlds)) return true;
            return false;
        }
        
        return false;
    }

    /**
     * @param Vector3 $source
     * @param Item $item
     * @param Player $damager
     * @param Vector3|null $motion
     * @param int $delay
     * @return ProtectedItemEntity|null
     */
    public function dropProtectedItem(Vector3 $source, Item $item, Player $damager, Vector3 $motion = null, int $delay = 10): ?ProtectedItemEntity
    {
        $motion = $motion ?? new Vector3(lcg_value() * 0.2 - 0.1, 0.2, lcg_value() * 0.2 - 0.1);
        $itemTag = $item->nbtSerialize();
        $itemTag->setName("Item");

        if ($item->isNull()) return null;

        $nbt = Entity::createBaseNBT($source, $motion, lcg_value() * 360, 0);
        $nbt->setShort("Health", 5);
        $nbt->setShort("PickupDelay", $delay);
        $nbt->setTag($itemTag);
        $itemEntity = new ProtectedItemEntity($damager->getLevel(), $nbt);

        if (!$itemEntity instanceof ProtectedItemEntity) return null;

        $timeSecs = $this->getConfig()->get("protection-time");
        if(!is_int($timeSecs)) {
            $timeSecs = 15;
        }
        $timeTicks = $timeSecs * 20;
        $itemEntity->setProtectionTime($timeTicks);

        if($this->getConfig()->get("enable-protection-message")) {
            $itemEntity->setProtectionMessage((string)$this->getConfig()->get("protection-message"));
        }
        $itemEntity->setOwner($damager->getName());
        $itemEntity->spawnToAll();
        return $itemEntity;
    }


}
