<?php

declare(strict_types = 1);

namespace Noob\BetterTrade\entity;

use pocketmine\entity\Living;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\math\{Vector2, Vector3};
use muqsit\customsizedinvmenu\CustomSizedInvMenu;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\transaction\InvMenuTransaction;
use pocketmine\item\StringToItemParser;
use Noob\BetterTrade\libs\jojoe77777\FormAPI\CustomForm;
use Noob\BetterTrade\libs\jojoe77777\FormAPI\SimpleForm;
use Noob\BetterTrade\libs\jojoe77777\FormAPI\ModalForm;
use Noob\BetterTrade\BetterTrade;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\scheduler\ClosureTask;
use pocketmine\item\Item;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\entity\Human;
use pocketmine\inventory\Inventory;

class VillagerTrade extends Human {

    public array $listTrade = [];
    public bool $firstUpdate = true;
  
    public function getName() : string{
        return "TradeNPC";
    }
 	
 	public function initEntity(CompoundTag $nbt): void{
        $this->setNameTagAlwaysVisible();
        $this->setNameTagVisible();
        parent::initEntity($nbt);
 	}
 	
 	public function entityBaseTick($tick = 1): bool {
        if($this->firstUpdate == true){
            if(BetterTrade::getInstance()->getTrader()->get($this->getNameTag()) == ""){
                $this->listTrade = [];
            }
            else $this->listTrade = unserialize(BetterTrade::getInstance()->getTrader()->get($this->getNameTag()));
            $this->firstUpdate = false;
        }
        $pos = $this->getPosition();
        $worldNPC = $this->getWorld()->getDisplayName();
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            if($player->isAlive() && $this->isAlive()){
                $worldPlayer = $player->getWorld()->getDisplayName();
                if($worldNPC == $worldPlayer){
                    $location = $player->getLocation();
                    if($pos->distance($player->getPosition()) < 5){
                        $this->lookAtLocation($location);
                    }
                }
            }
        }
        BetterTrade::getInstance()->getTask()->scheduleDelayedTask(new ClosureTask(function () : void {
            $this->updateTrade();
        }), 20 * 3);
        return parent::entityBaseTick($tick);
    }

    public function updateTrade(){
        BetterTrade::getInstance()->getTrader()->set($this->getNameTag(), serialize($this->listTrade));
        BetterTrade::getInstance()->getTrader()->save();
    }

    protected function lookAtLocation(Location $location): array{
        $angle = atan2($location->z - $this->getLocation()->z, $location->x - $this->getLocation()->x);
        $yaw = (($angle * 180) / M_PI) - 90;
        $angle = atan2((new Vector2($this->getLocation()->x, $this->getLocation()->z))->distance(new Vector2($location->x, $location->z)), $location->y - $this->getLocation()->y);
        $pitch = (($angle * 180) / M_PI) - 90;
 
        $this->setRotation($yaw, $pitch);

        return [$yaw, $pitch];
    }

    public function sendTradeGui(Player $player): void{
        $menu = CustomSizedInvMenu::create(54);
        $menu->setName($this->getNameTag());
        $inventory = $menu->getInventory();
        for($i = 36; $i <= 44; $i++){
            $border = StringToItemParser::getInstance()->parse("white_stained_glass_pane")->setCount(1)->setCustomName("§r§7 ");
            $inventory->setItem($i, $border);
        }
        for($i = 0; $i < count($this->listTrade); $i++){
                $trade = $this->listTrade[$i];
                $slot = (int)$trade["Slot"];
                $slotItemA = $slot - 18 - 9 - 9 - 9;
                $slotItemB = $slot - 18 - 9 - 9;
                $slotItemC = $slot - 18 - 9;
                $slotItemD = $slot - 18;
                $inventory->setItem($slot, BetterTrade::getInstance()->dataToItem($trade["ItemSell"]));
                if($trade["ItemA"] != "") $inventory->setItem($slotItemA, BetterTrade::getInstance()->dataToItem($trade["ItemA"]));
                if($trade["ItemB"] != "") $inventory->setItem($slotItemB, BetterTrade::getInstance()->dataToItem($trade["ItemB"]));
                if($trade["ItemC"] != "") $inventory->setItem($slotItemC, BetterTrade::getInstance()->dataToItem($trade["ItemC"]));
                if($trade["ItemD"] != "") $inventory->setItem($slotItemD, BetterTrade::getInstance()->dataToItem($trade["ItemD"]));
        }
        $menu->setListener(function(InvMenuTransaction $transaction) use ($player): InvMenuTransactionResult{
            $itemClicked = $transaction->getItemClicked();
            $itemClickedWith = $transaction->getItemClickedWith();
            $action = $transaction->getAction();
            if($itemClicked->getName() == "§r§7 " || !in_array($action->getSlot(), [45, 46, 47, 48, 49, 50, 51, 52, 53])){
                return $transaction->discard();
            }
            if(in_array($action->getSlot(), [45, 46, 47, 48, 49, 50, 51, 52, 53])){
                $slot = $action->getSlot();
                $slotItemA = $slot - 18 - 9 - 9 - 9;
                $slotItemB = $slot - 18 - 9 - 9;
                $slotItemC = $slot - 18 - 9;
                $slotItemD = $slot - 18;
                $itemA = $action->getInventory()->getItem($slotItemA);
                $itemB = $action->getInventory()->getItem($slotItemB);
                $itemC = $action->getInventory()->getItem($slotItemC);
                $itemD = $action->getInventory()->getItem($slotItemD);
                $itemArray = $this->getItemArray($itemA, $itemB, $itemC, $itemD); 
                $canTrade = false;
                $count = 0;
                $countArray = [];
                foreach($itemArray as $itemTrade){
                    $count = 0;
                    $itemNeed = BetterTrade::getInstance()->dataToItem($itemTrade);
                    if(!$itemA->isNull()){
                        if($itemA->equals($itemNeed)){
                            $count += $itemA->getCount();
                        }
                    }
                    if(!$itemB->isNull()){
                        if($itemB->equals($itemNeed)){
                            $count += $itemB->getCount();
                        }
                    }
                    if(!$itemC->isNull()){
                        if($itemC->equals($itemNeed)){
                            $count += $itemC->getCount();
                        }
                    }
                    if(!$itemD->isNull()){
                        if($itemD->equals($itemNeed)){
                            $count += $itemD->getCount();
                        }
                    }
                    $countArray[] = $count;
                }
                $countPlayerArray = [];
                foreach($itemArray as $itemTrade){
                    $count = 0;
                    $itemNeed = BetterTrade::getInstance()->dataToItem($itemTrade);
                    foreach($player->getInventory()->getContents() as $inv){
                        if($inv->equals($itemNeed)){
                            $count += $inv->getCount();
                        }
                    }
                    $countPlayerArray[] = $count;
                }
                $dem = 0;
                for($i = 0; $i < count($itemArray); $i++){
                    if($countPlayerArray[$i] >= $countArray[$i]) $dem++;
                }
                if($dem == count($itemArray)) $canTrade = true;
                if($canTrade == true){
                    if(!$itemA->isNull()){
                        $player->getInventory()->removeItem($itemA);
                    }
                    if(!$itemB->isNull()){
                        $player->getInventory()->removeItem($itemB);
                    }
                    if(!$itemC->isNull()){
                        $player->getInventory()->removeItem($itemC);
                    }
                    if(!$itemD->isNull()){
                        $player->getInventory()->removeItem($itemD);
                    }
                    $player->getInventory()->addItem($action->getInventory()->getItem($slot));
                    BetterTrade::getInstance()->sendSound($player, "random.levelup");
                    return $transaction->discard();
                }
                else{
                    BetterTrade::getInstance()->sendSound($player, "mob.horse.angry");
                    return $transaction->discard();
                }
            }
            return $transaction->continue();
            
        });
        $menu->send($player);
    }

    public function getItemArray(Item $itemA, Item $itemB, Item $itemC, Item $itemD): array{
        $itemList = [];
        if(!$itemA->isNull()){
            if(!in_array(BetterTrade::getInstance()->itemToData($itemA), $itemList)){
                $itemList[] = BetterTrade::getInstance()->itemToData($itemA);
            }
        }
        if(!$itemB->isNull()){
            if(!in_array(BetterTrade::getInstance()->itemToData($itemB), $itemList)){
                $itemList[] = BetterTrade::getInstance()->itemToData($itemB);
            }
        }
        if(!$itemC->isNull()){
            if(!in_array(BetterTrade::getInstance()->itemToData($itemC), $itemList)){
                $itemList[] = BetterTrade::getInstance()->itemToData($itemC);
            }
        }
        if(!$itemD->isNull()){
            if(!in_array(BetterTrade::getInstance()->itemToData($itemD), $itemList)){
                $itemList[] = BetterTrade::getInstance()->itemToData($itemD);
            }
        }
        return $itemList;
    }

    public function sendEditMenu(Player $player){
        $form = new SimpleForm(function(Player $player, $data){
            if($data === null){
                return true;
            }
            $prefix = BetterTrade::$prefix;
            switch($data){
                case 0: $this->rename($player); break;
                case 1: $this->edit($player); break;
                case 2:
                    $this->close();
                    $player->sendMessage($prefix . "Remove Villager Successfully !");
                    BetterTrade::getInstance()->getTrader()->remove($this->getNameTag());
                    BetterTrade::getInstance()->getTrader()->save();
                    BetterTrade::getInstance()->removeEditor($player);
                    break;
            }
        });
        $form->setTitle("Edit Villager ". $this->getNameTag());
        $form->addButton("Rename Villager");
        $form->addButton("Edit Trade");
        $form->addButton("Delete Villager");
        $form->sendToPlayer($player);
    }

    public function rename(Player $player){
        $form = new CustomForm(function(Player $player, $data){
            if($data === null){
                return true;
            }
            $prefix = BetterTrade::$prefix;
            if(!isset($data[0])){
                $player->sendMessage($prefix . "Please, Enter New Name For Villager !");
                return true;
            }
            $this->setNameTag($data[0]);
            $player->sendMessage($prefix . "Set Name Of Villager To ". $data[0] . " Successfully");
            BetterTrade::getInstance()->removeEditor($player);
        });
        $form->setTitle("Rename Villager ". $this->getNameTag());
        $form->addInput("Enter New Name For Villager:", "NoobLovePMMP");
        $form->sendToPlayer($player);
    }

    public function edit(Player $player): void{
        $menu = CustomSizedInvMenu::create(63);
        $menu->setName($this->getNameTag());
        $inventory = $menu->getInventory();
        for($i = 36; $i <= 44; $i++){
            $border = StringToItemParser::getInstance()->parse("white_stained_glass_pane")->setCount(1)->setCustomName("§r§7 ");
            $inventory->setItem($i, $border);
        }
        for($i = 54; $i <= 61; $i++){
            $border = StringToItemParser::getInstance()->parse("vine")->setCount(1)->setCustomName("§r§7 ");
            $inventory->setItem($i, $border);
        }
        for($i = 0; $i < count($this->listTrade); $i++){
            $trade = $this->listTrade[$i];
            $slot = (int)$trade["Slot"];
            $slotItemA = $slot - 18 - 9 - 9 - 9;
            $slotItemB = $slot - 18 - 9 - 9;
            $slotItemC = $slot - 18 - 9;
            $slotItemD = $slot - 18;
            $inventory->setItem($slot, BetterTrade::getInstance()->dataToItem($trade["ItemSell"]));
            if($trade["ItemA"] != "") $inventory->setItem($slotItemA, BetterTrade::getInstance()->dataToItem($trade["ItemA"]));
            if($trade["ItemB"] != "") $inventory->setItem($slotItemB, BetterTrade::getInstance()->dataToItem($trade["ItemB"]));
            if($trade["ItemC"] != "") $inventory->setItem($slotItemC, BetterTrade::getInstance()->dataToItem($trade["ItemC"]));
            if($trade["ItemD"] != "") $inventory->setItem($slotItemD, BetterTrade::getInstance()->dataToItem($trade["ItemD"]));
        }
        $item = StringToItemParser::getInstance()->parse("chest")->setCustomName("§aSave item");
        $enchant = StringToEnchantmentParser::getInstance()->parse("unbreaking");
        $item->addEnchantment(new EnchantmentInstance($enchant, 1000));
        $inventory->setItem(62, $item);
        $menu->setListener(function(InvMenuTransaction $transaction) use ($player): InvMenuTransactionResult{
            $itemClicked = $transaction->getItemClicked();
            $itemClickedWith = $transaction->getItemClickedWith();
            $action = $transaction->getAction();
            $prefix = BetterTrade::$prefix;
            if($itemClicked->getName() == "§r§7 " || $itemClicked->getName() == "vine"){
                return $transaction->discard();
            }
            if($itemClicked->getName() == "§aSave item"){
                $player->removeCurrentWindow();
                return $transaction->discard();
            }
            return $transaction->continue();
            
        });
        $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory){
            $this->listTrade = [];
            $prefix = BetterTrade::$prefix;
            for($i = 45; $i <= 53; $i++){
                $itemSell = $inventory->getItem($i);
                if(!$itemSell->isNull()){
                    $itemA = "";
                    $itemB = "";
                    $itemC = "";
                    $itemD = "";
                    $slotItemA = $i - 18 - 9 - 9 - 9;
                    $slotItemB = $i - 18 - 9 - 9;
                    $slotItemC = $i - 18 - 9;
                    $slotItemD = $i - 18;
                    if(!$inventory->getItem($slotItemD)->isNull()) $itemD = BetterTrade::getInstance()->itemToData($inventory->getItem($slotItemD));
                    if(!$inventory->getItem($slotItemC)->isNull()) $itemC = BetterTrade::getInstance()->itemToData($inventory->getItem($slotItemC));
                    if(!$inventory->getItem($slotItemB)->isNull()) $itemB = BetterTrade::getInstance()->itemToData($inventory->getItem($slotItemB));
                    if(!$inventory->getItem($slotItemA)->isNull()) $itemA = BetterTrade::getInstance()->itemToData($inventory->getItem($slotItemA));
                    $this->editItem($i, $itemA, $itemB, $itemC, $itemD, BetterTrade::getInstance()->itemToData($itemSell));
                    BetterTrade::getInstance()->removeEditor($player);
                }
            }
            $player->sendMessage($prefix . "Save Item Successfully");
        });
        $menu->send($player);
    }

    public function editItem(int $slot, string $itemA, string $itemB, string $itemC, string $itemD, string $itemSell){
        $trade = $this->listTrade;
        $trade[] = 
            [
                "Slot" => $slot,
                "ItemA" => $itemA,
                "ItemB" => $itemB,
                "ItemC" => $itemC,
                "ItemD" => $itemD,
                "ItemSell" => $itemSell
            ];
        $this->listTrade = $trade;
    }
}
