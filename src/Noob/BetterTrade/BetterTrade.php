<?php

namespace Noob\BetterTrade;

use pocketmine\event\Listener;
use pocketmine\item\VanillaItems;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Server;
use Noob\BetterTrade\commands\TradeCommand;
use Noob\BetterTrade\listener\EventListener;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\item\Item;
use Noob\BetterTrade\RegisterVillager;
use Noob\BetterTrade\entity\VillagerTrade;

class BetterTrade extends PluginBase {

    public array $editData = [];

    public $tradeData;
    public static string $prefix = "[BetterTrade] ";
	public static $instance;

	public static function getInstance() : self {
		return self::$instance;
	}

	public function onEnable(): void{
        self::$instance = $this;
        RegisterVillager::init();
        $this->getServer()->getCommandMap()->register("bettertrade", new TradeCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->tradeData = new Config($this->getDataFolder() . "trade_data.yml", Config::YAML);
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
	}

    public function sendSound(Player $player, string $soundName) {
        $packet = new PlaySoundPacket();
        $packet->soundName = $soundName;
        $packet->x = $player->getPosition()->getX();
        $packet->y = $player->getPosition()->getY();
        $packet->z = $player->getPosition()->getZ();
        $packet->volume = 1;
        $packet->pitch = 1;
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    public function getEditor(){
        return $this->editData;
    }

    public function getTrader(){
        return $this->tradeData;
    }

    public function getTask(){
        return $this->getScheduler();
    }

    public function getStringAfterSubCommand(array $args): string{
        $substr = "";
        for($i = 1; $i < count($args); $i++){
            if($i == count($args) - 1){
                $substr .= $args[$i];
            }
            else{
                $substr .= $args[$i];
                $substr .= " ";
            }
        }
        return $substr;
    }

    public function addEditor(Player $player): void{
        $data = $this->getEditor();
        $data[] = $player->getName();
        $this->editData = $data;
    }

    public function removeEditor(Player $player): void{
        $newData = [];
        $data = $this->getEditor();
        foreach($data as $editor){
            if($editor != $player->getName()) $newData[] = $editor;
        }
        $this->editData = $newData;
    }

    public function itemToData(Item $item): string {
        $cloneItem = clone $item;
        $itemNBT = $cloneItem->nbtSerialize();
        return base64_encode(serialize($itemNBT));
    }

    public function dataToItem(string $item): Item{
        $itemNBT = unserialize(base64_decode($item));
        return Item::nbtDeserialize($itemNBT);
    }
}