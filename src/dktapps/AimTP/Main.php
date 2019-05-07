<?php

declare(strict_types=1);

namespace dktapps\AimTP;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\world\World;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{
	private const AIMSTICK_TAG = 'tpstick';

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case 'tpstick':
				if(!($sender instanceof Player)){
					if(!isset($args[1]) or ($player = $sender->getServer()->getPlayer($args[1])) === null){
						$sender->sendMessage(TextFormat::RED . "You must specify a player from the console");
						return true;
					}
				}else{
					$player = $sender;
				}
				$stick = ItemFactory::get(Item::STICK);
				$stick->setCustomName("Teleporter Stick");
				$stick->getNamedTag()->setByte(self::AIMSTICK_TAG, 1);
				$player->getInventory()->addItem($stick);
				Command::broadcastCommandMessage($sender, "Gave " . $sender->getName() . " a teleporter stick");
				return true;
			default:
				return false;
		}
	}

	public function onBreakBlock(BlockBreakEvent $event){
		//prevent PE breaking blocks by accident
		if($event->getItem()->getNamedTag()->hasTag(self::AIMSTICK_TAG)){
			$event->setCancelled();
		}
	}

	public function onItemUse(PlayerItemUseEvent $event){
		if($event->getItem()->getNamedTag()->hasTag(self::AIMSTICK_TAG)){
			$player = $event->getPlayer();
			if(!$player->hasPermission('aimtp.use')){
				$player->sendMessage(TextFormat::RED . 'You don\'t have permission to use this item');
				return;
			}
			$start = $player->add(0, $player->getEyeHeight(), 0);
			$end = $start->add($player->getDirectionVector()->multiply($player->getViewDistance() * 16));
			$world = $player->world;

			foreach(VoxelRayTrace::betweenPoints($start, $end) as $vector3){
				if($vector3->y >= World::Y_MAX or $vector3->y <= 0){
					return;
				}

				if(!$world->isChunkLoaded($vector3->x >> 4, $vector3->z >> 4) or !$world->getChunk($vector3->x >> 4, $vector3->z >> 4)->isGenerated()){
					return;
				}

				if(($result = $world->getBlockAt($vector3->x, $vector3->y, $vector3->z)->calculateIntercept($start, $end)) !== null){
					$target = $result->hitVector;
					$player->teleport($target);
					return;
				}
			}
		}
	}
}
