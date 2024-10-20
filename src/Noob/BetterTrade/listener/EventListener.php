<?php

namespace Noob\BetterTrade\listener;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerQuitEvent;
use Noob\BetterTrade\entity\VillagerTrade;
use pocketmine\player\Player;
use Noob\BetterTrade\BetterTrade;

class EventListener implements Listener {
    
    public function onClick(EntityDamageByEntityEvent $ev) {
        $player = $ev->getDamager();
        $entity = $ev->getEntity();
        if ($entity instanceof VillagerTrade && $player instanceof Player) {
            if(in_array($player->getName(), BetterTrade::getInstance()->getEditor())){
                $entity->sendEditMenu($player);
            }
            else $entity->sendTradeGui($player);
            $ev->cancel();
        }
    }

    public function onQuit(PlayerQuitEvent $ev){
        $player = $ev->getPlayer();
        if(in_array($player->getName(), BetterTrade::getInstance()->getEditor())){
            BetterTrade::getInstance()->removeEditor($player);
        }
    }
}