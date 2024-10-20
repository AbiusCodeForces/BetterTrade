<?php

declare(strict_types = 1);

namespace Noob\BetterTrade;

use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use pocketmine\Server;
use Noob\BetterTrade\entity\VillagerTrade;
use pocketmine\entity\Human;

final class RegisterVillager {

	public static function init() : void {
		self::registerEntities();
	}

	private static function registerEntities() : void {
		foreach (self::getAllEntities() as $name => $class) {
			EntityFactory::getInstance()->register($class, function (World $world, CompoundTag $nbt) use ($class) : Entity {
				return new $class(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
			}, [$name]);
		}
	}
	
	public static function getAllEntities(): array {
	  	$entity = [
	    	"VillagerTrade"=>VillagerTrade::class
	    ];
		return $entity;
	}
} 