<?php

declare(strict_types=1);

namespace AndreasHGK\SellAll;

use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\item\Item;
use onebone\economyapi\EconomyAPI;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class SellAll extends PluginBase implements Listener {

    const CFGVERSION = 1.3;

    public $cfg;
    public $msg;
    public $msgfile;
    public $multiplier;

    public function getSellPrice(Item $item) : ?float {
        return $this->cfg[$item->getID().":".$item->getDamage()] ?? $this->cfg[$item->getID()] ?? null;
    }

    public function isSellable(Item $item) : bool{
        return $this->getSellPrice($item) !== null ? true : false;
    }

	public function onEnable() : void{
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->cfg = $this->getConfig()->getAll();
        $this->saveResource("messages.yml");
		$this->multiplier = new Config($this->getDataFolder() . "multipliers.yml", Config::YAML);
        $this->msgfile = new Config($this->getDataFolder() . "messages.yml", Config::YAML, []);
        $this->msg = $this->msgfile->getAll();
        if(!isset($this->cfg["cfgversion"])){
            $this->getLogger()->critical("config version outdated! please regenerate your config or this plugin might not work correctly.");
        }elseif($this->cfg["cfgversion"] != self::CFGVERSION){
            $this->getLogger()->critical("config version outdated! please regenerate your config or this plugin might not work correctly.");
        }
        if(!isset($this->msg["cfgversion"])){
            $this->getLogger()->critical("messages version outdated! please regenerate your messages file or this plugin might not work correctly.");
        }elseif($this->msg["cfgversion"] != self::CFGVERSION){
            $this->getLogger()->critical("messages version outdated! please regenerate messages file config or this plugin might not work correctly.");
        }
	}

    public function replaceVars($str, array $vars) : string{
        foreach($vars as $key => $value){
            $str = str_replace("{" . $key . "}", $value, $str);
        }
        return $str;
    }

    public function onJoin(PlayerJoinEvent $event) : void {
    	$player = $event->getPlayer();
    	if (!$this->multiplier->exists($player->getName())) {
    		$this->multiplier->set($player->getName(), 1);
    		$this->multiplier->save();
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!($sender instanceof Player) && !(isset($args[0]) && $args[0] === "reload")){
            $sender->sendMessage(TextFormat::colorize("&cPlease execute this command in-game"));
            return true;
        }
		switch($command->getName()){
			case "addmultiplier":
				if (!$sender->hasPermission("sellall.addmultiplier")) {
					$sender->sendMessage(TextFormat::colorize($this->msg["error.permission"]));
					return true;
				}
				if (empty($args[0])) {
					$sender->sendMessage(TextFormat::RED . "Example: /addmultiplier (player) (amount)");
					return true;
				}
				if (empty($args[1])) {
					$sender->sendMessage(TextFormat::RED . "Example: /addmultiplier (player) (amount)");
					return true;
				}
				$p = $this->getServer()->getPlayer($args[0]);
				if ($p == null) {
					$sender->sendMessage(TextFormat::RED . "That Player Is Not Online!");
					return true;
				}
				if (!is_numeric($args[1])) {
					$sender->sendMessage(TextFormat::RED . "The amount must be numeric!");
					return true;
				}
				$this->multiplier->set($p->getName(), $this->multiplier->get($p->getName()) + $args[1]);
				$this->multiplier->save();
				$sender->sendMessage(TextFormat::GREEN . "Succesfully Set " . $p->getName() . "'s Multiplier To x" . $this->multiplier->get($p->getName()));
				return true;
				break;
			case "sell":
                if(!$sender->hasPermission("sellall.command")){
                    $sender->sendMessage(TextFormat::colorize($this->msg["error.permission"]));
                    return true;
                }
				if(isset($args[0])){
				    switch(strtolower($args[0])){
                        case "hand":
                            $item = $sender->getInventory()->getItemInHand();
                            if(isset($this->cfg[$item->getID().":".$item->getDamage()])){
                                $price = $this->cfg[$item->getID().":".$item->getDamage()];
                                $count = $item->getCount();
                                $totalprice = $price * $count;
                                $actualprice = $totalprice * $this->multiplier->get($sender->getName());
                                EconomyAPI::getInstance()->addMoney($sender->getName(), (int)$actualprice);
                                $item->setCount($item->getCount() - (int)$count);
                                $sender->getInventory()->setItemInHand($item);
                                $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->msg["success.sell"], array(
                                    "AMOUNT" => (string)$count,
                                    "ITEMNAME" => $item->getName(),
                                    "MONEY" => (string)$actualprice))));
                                return true;
                            }elseif(isset($this->cfg[$item->getID()])){
                                $price = $this->cfg[$item->getID()];
                                $count = $item->getCount();
                                $totalprice = $price * $count;
								$actualprice = $totalprice * $this->multiplier->get($sender->getName());
                                EconomyAPI::getInstance()->addMoney($sender->getName(), (int)$actualprice);
                                $item->setCount($item->getCount() - (int)$count);
                                $sender->getInventory()->setItemInHand($item);
                                $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->msg["success.sell"], array(
                                    "AMOUNT" => (string)$count,
                                    "ITEMNAME" => $item->getName(),
                                    "MONEY" => (string)$actualprice))));
                                return true;
                            }
                            $sender->sendMessage(TextFormat::colorize($this->msg["error.not-found"]));
                            return true;
                            break;

                        case "all":
                            $item = $sender->getInventory()->getItemInHand();
                            $inventory = $sender->getInventory();
                            $contents = $inventory->getContents();
                            if(isset($this->cfg[$item->getID().":".$item->getDamage()])){
                                $price = $this->cfg[$item->getID().":".$item->getDamage()];
                                $count = 0;
                                foreach($contents as $slot){
                                    if($slot->getID() == $item->getId()){
                                        $count = $count + $slot->getCount();
                                        $inventory->remove($slot);
                                    }
                                }
                                $inventory->sendContents($sender);
                                $totalprice = $count * $price;
								$actualprice = $totalprice * $this->multiplier->get($sender->getName());
                                EconomyAPI::getInstance()->addMoney($sender->getName(), (int)$actualprice);
                                $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->msg["success.sell"], array(
                                    "AMOUNT" => (string)$count,
                                    "ITEMNAME" => $item->getName(),
                                    "MONEY" => (string)$actualprice))));
                                return true;
                            }elseif(isset($this->cfg[$item->getID()])){
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
								$actualprice = $totalprice * $this->multiplier->get($sender->getName());
                                EconomyAPI::getInstance()->addMoney($sender->getName(), (int)$actualprice);
                                $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->msg["success.sell"], array(
                                    "AMOUNT" => (string)$count,
                                    "ITEMNAME" => $item->getName(),
                                    "MONEY" => (string)$actualprice))));
                                return true;
                            }
                            $sender->sendMessage(TextFormat::colorize($this->msg["error.not-found"]));
                            return true;
                            break;

						case "inv":
                        case "inventory":
                            $inv = $sender->getInventory()->getContents();
                            $revenue = 0;
                            foreach($inv as $item){
                                if(isset($this->cfg[$item->getID().":".$item->getDamage()])){
                                    $revenue = $revenue + ($item->getCount() * $this->cfg[$item->getID().":".$item->getDamage()]);
                                    $sender->getInventory()->remove($item);
                                }elseif(isset($this->cfg[$item->getID()])){
                                    $revenue = $revenue + ($item->getCount() * $this->cfg[$item->getID()]);
                                    $sender->getInventory()->remove($item);
                                }
                            }
                            if($revenue <= 0){
                                $sender->sendMessage(TextFormat::colorize($this->msg["error.no.sellables"]));
                                return true;
                            }
							$actualprice = $revenue * $this->multiplier->get($sender->getName());
                            EconomyAPI::getInstance()->addMoney($sender->getName(), (int)$actualprice);
                            $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->msg["success.sell.inventory"], array(
                                "MONEY" => (string)$actualprice))));
                            return true;
                            break;

                        case "reload":
                            if($sender->hasPermission("sellall.reload")){
                                $this->reloadConfig();
                                $this->cfg = $this->getConfig()->getAll();
                                $this->msgfile = new Config($this->getDataFolder() . "messages.yml", Config::YAML, []);
                                $this->msg = $this->msgfile->getAll();
                                if(!isset($this->cfg["cfgversion"])){
                                    $this->getLogger()->critical("config version outdated! please regenerate your config or this plugin might not work correctly.");
                                }elseif($this->cfg["cfgversion"] != self::CFGVERSION){
                                    $this->getLogger()->critical("config version outdated! please regenerate your config or this plugin might not work correctly.");
                                }
                                if(!isset($this->msg["cfgversion"])){
                                    $this->getLogger()->critical("messages version outdated! please regenerate your messages file or this plugin might not work correctly.");
                                }elseif($this->msg["cfgversion"] != self::CFGVERSION){
                                    $this->getLogger()->critical("messages version outdated! please regenerate messages file config or this plugin might not work correctly.");
                                }
                                $sender->sendMessage(TextFormat::colorize($this->msg["reload"]));
                            }else{
                                $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->msg["error.argument"], array(
                                    "ARGS" => $this->listArguments()))));
                                return true;
                            }
                            return true;
                            break;

                        default:
                            if(array_key_exists($args[0], $this->cfg["groups"])){
                                $group = $this->cfg["groups"][$args[0]];

                                $inv = $sender->getInventory()->getContents();
                                $revenue = 0;
                                foreach($inv as $item){
                                    if(isset($this->cfg[$item->getID()])){
                                        if(in_array($item->getId(), $group["items"]) || in_array($item->getName(), $group["items"])){
                                            if(isset($this->cfg[$item->getID().":".$item->getDamage()])){
                                                $revenue = $revenue + ($item->getCount() * $this->cfg[$item->getID().":".$item->getDamage()]);
                                                $sender->getInventory()->remove($item);
                                            }elseif(isset($this->cfg[$item->getID()])){
                                                $revenue = $rev$actualprice = $totalprice * $this->multiplier->get($sender->getName());enue + ($item->getCount() * $this->cfg[$item->getID()]);
                                                $sender->getInventory()->remove($item);
                                            }
                                        }
                                    }
                                }
                                if($revenue <= 0){
                                    $sender->sendMessage(TextFormat::colorize($group["failed"]));
                                    return true;
                                }
								$actualprice = $revenue * $this->multiplier->get($sender->getName());
                                EconomyAPI::getInstance()->addMoney($sender->getName(), (int)$actualprice);
                                $sender->sendMessage(TextFormat::colorize($this->replaceVars($group["success"], array(
                                    "MONEY" => (string)$actualprice))));
                                return true;
                            }
                            $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->msg["error.argument"], array(
                                "ARGS" => $this->listArguments()))));
                            return true;

                    }
                }
                $sender->sendMessage(TextFormat::colorize($this->replaceVars($this->msg["error.argument"], array(
                    "ARGS" => $this->listArguments()))));
				return true;
			default:
				return false;
		}
	}

	public function listArguments() : string{
	    $seperator = $this->msg["separator"];
	    $args = "hand".$seperator."all".$seperator."inv";
	    foreach($this->cfg["groups"] as $name => $group){
	        $args = $args.$seperator.$name;
        }
        return $args;
    }

}
