<?php

namespace Heisenburger69\BurgerDropsProtection;

use pocketmine\entity\object\ItemEntity;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\Player;

class ProtectedItemEntity extends ItemEntity
{

    /** @var int */
    private $protectionTime = 200; //in ticks ree

    public function onCollideWithPlayer(Player $player): void
    {
        if ($this->getPickupDelay() !== 0) {
            return;
        }
        if ($player->getName() !== $this->owner && $this->age < $this->getProtectionTime()) {
            return;
        }
        $item = $this->getItem();
        $playerInventory = $player->getInventory();

        if ($player->isSurvival() and !$playerInventory->canAddItem($item)) {
            return;
        }

        $ev = new InventoryPickupItemEvent($playerInventory, $this);
        $ev->call();
        if ($ev->isCancelled()) {
            return;
        }

        switch ($item->getId()) {
            case Item::WOOD:
                $player->awardAchievement("mineWood");
                break;
            case Item::DIAMOND:
                $player->awardAchievement("diamond");
                break;
        }

        $pk = new TakeItemActorPacket();
        $pk->eid = $player->getId();
        $pk->target = $this->getId();
        $this->server->broadcastPacket($this->getViewers(), $pk);

        $playerInventory->addItem(clone $item);
        $this->flagForDespawn();
    }

    public function getProtectionTime(): int
    {
        return $this->protectionTime;
    }

    public function setProtectionTime(int $ticks): void
    {
        $this->protectionTime = $ticks;
    }
}