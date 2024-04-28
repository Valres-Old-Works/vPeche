<?php

namespace Valres\Peche;

use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use Valres\Peche\item\FishingRod;
use Valres\Peche\libs\Override\Override;

class Peche extends PluginBase
{
    use SingletonTrait;

    protected function onEnable(): void
    {
        $this->saveDefaultConfig();
        $this->getLogger()->info("by Valres est lancé !");
    }

    protected function onLoad(): void
    {
        self::setInstance($this);
        $fishingRod = new FishingRod(new ItemIdentifier(ItemTypeIds::FISHING_ROD), "Fishing Rod");
        Override::override("fishing_rod", $fishingRod);
        Override::deserializer(ItemTypeNames::FISHING_ROD, $fishingRod, function () use ($fishingRod) {
            return clone $fishingRod;
        });
        Override::serializer(ItemTypeNames::FISHING_ROD, $fishingRod);
        Override::resetCreativeInventory();
    }
}