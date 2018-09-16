<?php

declare(strict_types=1);

namespace AndreasHGK\SellAll;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\item\Item;
use onebone\economyapi\EconomyAPI;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

class SellAll extends PluginBase{

    public $cfg;

	public function onEnable() : void{
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->cfg = $this->getConfig()->getAll();
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!($sender instanceof Player)){
            $sender->sendMessage(C::colorize("&cPlease execute this command in-game"));
            return true;
        }
		switch($command->getName()){
			case "sell":
                if(!$sender->hasPermission("sellall.command")){
                    $sender->sendMessage(TextFormat::colorize("&c&lError: &r&7you don't have permission to execute this command"));
                    return true;
                }
				if(isset($args[0])){
				    switch(strtolower($args[0])){
                        case "hand":
                            $item = $sender->getInventory()->getItemInHand();
                            if(isset($this->cfg[$item->getID()])){
                                $price = $this->cfg[$item->getID()];
                                $count = $item->getCount();
                                $totalprice = $price * $count;
                                EconomyAPI::getInstance()->addMoney($sender->getName(), (int)$totalprice);
                                $item->setCount($item->getCount() - (int)$count);
                                $sender->getInventory()->setItemInHand($item);
                                $sender->sendMessage(TextFormat::colorize("&a&lSuccess! &r&7sold &8".(string)$count." ".$item->getName()."(s) &7for &8$".(string)$totalprice));
                                return true;
                            }
                            $sender->sendMessage(TextFormat::colorize("&c&lError: &r&7you can't sell this item"));
                            return true;
                            break;

                        case "all":
                            return true;

                        default:
                            $sender->sendMessage(TextFormat::colorize("&c&lError: &r&7please enter a valid argument"));
                            return true;

                    }
                }
                $sender->sendMessage(TextFormat::colorize("&c&lError: &r&7please enter a valid argument"));
				return true;
			default:
				return false;
		}
	}

}
