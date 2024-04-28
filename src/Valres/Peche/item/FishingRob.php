<?php

namespace Valres\Peche\item;

use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\Releasable;
use pocketmine\item\StringToItemParser;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ThrowSound;
use pocketmine\world\sound\XpLevelUpSound;
use Valres\Peche\entity\FishingHook;
use pocketmine\item\ItemUseResult;
use Valres\Peche\event\PlayerFishEvent;
use Valres\Peche\Peche;

class FishingRob extends Durable implements Releasable
{
    private static array $cache = [];
    /** @var Item[] */
    private array $rewards = [];

    public function __construct(ItemIdentifier $identifier, string $name = "Unknown", array $enchantmentTags = [])
    {
        $config = Peche::getInstance()->getConfig();
        foreach($config->get("rewards") as $reward){
            $this->rewards[] = StringToItemParser::getInstance()->parse($reward);
        }
        parent::__construct($identifier, $name, $enchantmentTags);
    }

    public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult {
        if(isset(self::$cache[$player->getName()])){
            /** @var FishingHook $e */
            $e = self::$cache[$player->getName()];
            if(!$e->isClosed() && !$e->isFlaggedForDespawn()){
                if($e->isActive()){
                    $this->onCatch($player);
                }

                self::$cache[$player->getName()]->flagForDespawn();

                self::$cache[$player->getName()] = null;
                return parent::onClickAir($player, $directionVector, $returnedItems);
            }
        }

        $loc = $player->getLocation();
        $loc->y += 1;
        $player->broadcastSound(new ThrowSound());
        $e = new FishingHook($loc, $player);

        $e->setMaxFishTime((mt_rand((int) self::getMinFishTimeInSeconds(), (int) self::getMaxFishTimeInSeconds())));

        $e->handleHookCasting($player->getDirectionVector()->multiply(2), 2, 2);
        $e->setOwningEntity($player);
        $e->spawnToAll();
        self::$cache[$player->getName()] = $e;

        return parent::onClickAir($player, $directionVector, $returnedItems);
    }

    public function onCatch(Player $player): void {
        /** @var FishingHook $e */
        $reward = $this->getRandomReward($player);
        $event = new PlayerFishEvent($player, $this, $reward, mt_rand(1, 5));
        $event->call();

        $player->getInventory()->canAddItem($reward) ? $player->getInventory()->addItem($reward) : $player->getWorld()->dropItem($player->getPosition()->asVector3(), $reward);
        $player->broadcastSound(new XpLevelUpSound(3));
        $player->getXpManager()->addXp(rand(1, 5));
        $this->applyDamage(1);
        $player->getInventory()->setItemInHand($this);
    }

    public function getRandomReward(Player $player): Item
    {
        return $this->rewards[array_rand($this->rewards)];
    }

    public function getMaxStackSize(): int
    {
        return 1;
    }

    public static function getMinFishTimeInSeconds(): float
    {
        $config = Peche::getInstance()->getConfig();
        return $config->get("fish-time")["min"];
    }

    public static function getMaxFishTimeInSeconds(): float
    {
        $config = Peche::getInstance()->getConfig();
        return $config->get("fish-time")["max"];
    }

    public function getMaxDurability(): int
    {
        $config = Peche::getInstance()->getConfig();
        return $config->get("durability");
    }

    public function canStartUsingItem(Player $player): bool
    {
        return true;
    }
}
