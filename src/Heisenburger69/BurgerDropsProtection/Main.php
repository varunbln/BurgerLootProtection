<?php

declare(strict_types=1);

namespace Heisenburger69\BurgerDropsProtection;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase
{

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        Entity::registerEntity(ProtectedItemEntity::class, true, ["protectedItemEntity"]);
        $this->saveDefaultConfig();
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

        $itemEntity->setOwner($damager->getName());
        $itemEntity->spawnToAll();
        return $itemEntity;
    }

}
