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

    public function replaceVars($str, array $vars) : string{
        foreach($vars as $key => $value){
            $str = str_replace("{" . $key . "}", $value, $str);
        }
        return $str;
    }

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!($sender instanceof Player)){
            $sender->sendMessage(C::colorize("&cPlease execute this command in-game"));
            return true;
        }
		switch($command->getName()){
			case "sell":
                if(!$sender->hasPermission("sellall.command")){
                    $sender->sendMessage(TextFormat::colorize($this->cfg["error.permission"]));
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
                                $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->cfg["success.sell"], array(
                                    "AMOUNT" => (string)$count,
                                    "ITEMNAME" => $item->getName(),
                                    "MONEY" => (string)$totalprice))));
                                return true;
                            }
                            $sender->sendMessage(TextFormat::colorize($this->cfg["error.not-found"]));
                            return true;
                            break;

                        case "all":
                            $item = $sender->getInventory()->getItemInHand();
                            $inventory = $sender->getInventory();
                            $contents = $inventory->getContents();
                            if(isset($this->cfg[$item->getID()])){
                                $price = $this->cfg[$item->getID()];
                                $count = 0;
                                foreach($contents as $slot){
                                    if($slot->getID() == $item->getId()){
                                        $count = $count + $slot->getCount();
                                        $inventory->remove($slot);
                                    }
                                }
                                $inventory->sendContents($sender);
                                $totalprice = $count * $price;
                                EconomyAPI::getInstance()->addMoney($sender->getName(), (int)$totalprice);
                                $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->cfg["success.sell"], array(
                                    "AMOUNT" => (string)$count,
                                    "ITEMNAME" => $item->getName(),
                                    "MONEY" => (string)$totalprice))));
                                return true;
                            }
                            $sender->sendMessage(TextFormat::colorize($this->cfg["error.not-found"]));
                            return true;
                            break;

                        default:
                            $sender->sendMessage(TextFormat::colorize($this->cfg["error.argument"]));
                            return true;

                    }
                }
                $sender->sendMessage(TextFormat::colorize($this->cfg["error.argument"]));
				return true;
			default:
				return false;
		}
	}

}
