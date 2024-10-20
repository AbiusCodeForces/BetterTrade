<?php

namespace Noob\BetterTrade\commands;

/*
███╗   ██╗██╗  ██╗██╗   ██╗████████╗    ██████╗ ███████╗██╗   ██╗
████╗  ██║██║  ██║██║   ██║╚══██╔══╝    ██╔══██╗██╔════╝██║   ██║
██╔██╗ ██║███████║██║   ██║   ██║       ██║  ██║█████╗  ██║   ██║
██║╚██╗██║██╔══██║██║   ██║   ██║       ██║  ██║██╔══╝  ╚██╗ ██╔╝
██║ ╚████║██║  ██║╚██████╔╝   ██║       ██████╔╝███████╗ ╚████╔╝ 
╚═╝  ╚═══╝╚═╝  ╚═╝ ╚═════╝    ╚═╝       ╚═════╝ ╚══════╝  ╚═══╝  
        Copyright © 2024 - 2025 NoobMCGaming
*/    

use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use Noob\BetterTrade\BetterTrade;
use pocketmine\Server;
use Noob\BetterTrade\entity\VillagerTrade;

class TradeCommand extends Command implements PluginOwned{
    private BetterTrade $plugin;

    public function __construct(BetterTrade $plugin)
    {
        $this->plugin = $plugin;
        parent::__construct("bettertrade", "Villager Trade", null, ["trade"]);
        $this->setPermission("bettertrade.cmd");
    }

    public function execute(CommandSender $player, string $label, array $args){
        if (!$player instanceof Player) {
            $this->getOwningPlugin()->getLogger()->notice("Xin hãy sử dụng lệnh trong trò chơi");
            return 1;
        }
        $prefix = BetterTrade::$prefix;
        if(!isset($args[0])){
            $player->sendMessage($prefix . "Usage: /bettertrade [create|edit]");
            return 1;
        }
        switch($args[0]){
            case "create":
                if(count($args) < 2){
                    $player->sendMessage($prefix . "Please, Enter Name Of Villager !");
                    return 1;
                }
                $name = $this->plugin->getStringAfterSubCommand($args);
                if($this->plugin->getTrader()->exists($name)){
                    $player->sendMessage($prefix . "This Villager's Name Already Exists !");
                    return 1;
                }
                $name = str_replace("&", "§", $name);
                $location = $player->getLocation();
                $villager = new VillagerTrade($location, $player->getSkin());
                $villager->spawnToAll();
                $villager->setNameTag($name);
                $this->plugin->getTrader()->set($name, "");
                $this->plugin->getTrader()->save();
                break;
            case "edit":
                if(!in_array($player->getName(), $this->plugin->getEditor())){
                    $this->plugin->addEditor($player);
                    $player->sendMessage($prefix . "Please, Click At Trader !");
                }
                break;
            default:
                $player->sendMessage($prefix . "Usage: /bettertrade [create|edit]");
                break;
        }
    }

    public function getOwningPlugin(): BetterTrade{
        return $this->plugin;
    }
}
