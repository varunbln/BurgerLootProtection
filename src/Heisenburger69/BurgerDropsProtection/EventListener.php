<?php

namespace Heisenburger69\BurgerDropsProtection;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\Player;

class EventListener implements Listener
{
    /**
     * @var Main
     */
    private $plugin;

    /**
     * EventListener constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerDeathEvent $event
     * @priority HIGH
     */
    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();

        if(!$this->plugin->getConfig()->get("enable-protection")) return;
        if(!$this->plugin->checkProtectionPerms($player)) return;
        if(!$this->plugin->checkProtectionLevel($player->getLevel())) return;

        $cause = $player->getLastDamageCause();
        if (!$cause instanceof EntityDamageByEntityEvent) {
            return;
        }

        $damager = $cause->getDamager();
        if (!$damager instanceof Player) {
            return;
        }

        foreach ($event->getDrops() as $drop) {
            $this->plugin->dropProtectedItem($player, $drop, $damager);
        }
        $event->setDrops([]);
    }
}