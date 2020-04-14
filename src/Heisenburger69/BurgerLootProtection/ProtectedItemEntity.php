<?php

namespace Heisenburger69\BurgerLootProtection;

use pocketmine\entity\object\ItemEntity;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ProtectedItemEntity extends ItemEntity
{

    /** @var int */
    private $protectionTime = 200; //in ticks

    /** @var string */
    private $protectionMessage;

    /**
     * @param Player $player
     */
    public function onCollideWithPlayer(Player $player): void
    {
        if ($this->getPickupDelay() !== 0) {
            return;
        }
        if ($player->getName() !== $this->owner && $this->age < $this->getProtectionTime()) {
            if(!Main::$instance->getConfig()->get("enable-protection-message")) return;

            $time = ceil(($this->getProtectionTime() - $this->age) / 20);
            $message = $this->getProtectionMessage();
            $message = str_replace(["{TIME}", "{KILLER}"], [$time, $this->owner], $message);
            $message = TextFormat::colorize($message);

            $player->sendTip($message);
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

    public function getProtectionMessage(): string
    {
        return $this->protectionMessage;
    }

    public function setProtectionMessage(string $message): void
    {
        $this->protectionMessage = $message;
    }
}