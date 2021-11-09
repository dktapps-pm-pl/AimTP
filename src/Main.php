<?php

declare(strict_types=1);

namespace dktapps\AimTP;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\math\VoxelRayTrace;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class Main extends PluginBase implements Listener{
	private const AIMSTICK_TAG = 'tpstick';

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		StringToItemParser::getInstance()->register("tpstick", function() : Item{
			$stick = VanillaItems::STICK();
			$stick->setCustomName("Teleporter Stick");
			$stick->getNamedTag()->setByte(self::AIMSTICK_TAG, 1);
			return $stick;
		});
	}

	public function onBreakBlock(BlockBreakEvent $event) : void{
		//prevent PE breaking blocks by accident
		if($event->getItem()->getNamedTag()->getTag(self::AIMSTICK_TAG) !== null){
			$event->cancel();
		}
	}

	public function onItemUse(PlayerItemUseEvent $event) : void{
		if($event->getItem()->getNamedTag()->getTag(self::AIMSTICK_TAG) !== null){
			$player = $event->getPlayer();
			if(!$player->hasPermission('aimtp.use')){
				$player->sendMessage(TextFormat::RED . 'You don\'t have permission to use this item');
				return;
			}
			$start = $player->getPosition()->add(0, $player->getEyeHeight(), 0);
			$end = $start->addVector($player->getDirectionVector()->multiply($player->getViewDistance() * 16));
			$world = $player->getWorld();

			foreach(VoxelRayTrace::betweenPoints($start, $end) as $vector3){
				if($vector3->y >= World::Y_MAX or $vector3->y <= 0){
					return;
				}

				if(!$world->isChunkLoaded($vector3->x >> Chunk::COORD_BIT_SIZE, $vector3->z >> Chunk::COORD_BIT_SIZE)){
					return;
				}

				if(($result = $world->getBlock($vector3)->calculateIntercept($start, $end)) !== null){
					$target = $result->hitVector;
					$player->teleport($target);
					return;
				}
			}
		}
	}
}
