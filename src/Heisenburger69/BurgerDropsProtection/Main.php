<?php

declare(strict_types=1);

namespace Heisenburger69\BurgerDropsProtection;

use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{
    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        Entity::registerEntity(ProtectedItemEntity::class, true, ["protectedItem"]);
    }

    /**
     * @param PlayerDeathEvent $event
     * @priority HIGH
     */
    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        if (!$cause instanceof EntityDamageByEntityEvent) {
            return;
        }

        $damager = $cause->getDamager();
        if (!$damager instanceof Player) {
            return;
        }

        foreach ($event->getDrops() as $drop) {
            $this->dropItem($player, $drop, $damager, null, 10);
        }
        $event->setDrops([]);
    }

    /**
     * @param Vector3 $source
     * @param Item $item
     * @param Player $damager
     * @param Vector3|null $motion
     * @param int $delay
     * @return ItemEntity|null
     */
    public function dropItem(Vector3 $source, Item $item, Player $damager, Vector3 $motion = null, int $delay = 10)
    {
        $motion = $motion ?? new Vector3(lcg_value() * 0.2 - 0.1, 0.2, lcg_value() * 0.2 - 0.1);
        $itemTag = $item->nbtSerialize();
        $itemTag->setName("Item");

        if (!$item->isNull()) {

            $nbt = Entity::createBaseNBT($source, $motion, lcg_value() * 360, 0);
            $nbt->setShort("Health", 5);
            $nbt->setShort("PickupDelay", $delay);
            $nbt->setTag($itemTag);
            $itemEntity = new ProtectedItemEntity($damager->getLevel(), $nbt);

            if ($itemEntity instanceof ProtectedItemEntity) {
                $itemEntity->setOwner($damager->getName());
                $itemEntity->spawnToAll();
                return $itemEntity;
            }
        }
        return null;
    }

}
