<?php
namespace Revenge;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;

class Main extends PluginBase implements Listener {
    
    public $busy = array();
    public $lastKiller = array();
    
    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info(Color::GREEN."Revenge - Enabled");
        return;
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        if($sender instanceof Player) {
            $level = $sender->getLevel()->getName();
            if($this->getConfig()->get("Enabled") == true) {
                if($this->getConfig()->getNested("enabledWorlds.$level") === 'enabled') {
                    if(!isset($this->busy[$sender->getName()])) {
                        if($this->isConfigSet()) {
                            if(strtolower($command) == 'revenge') {
                                switch (count($args)) {
                                    case 0:
                                        if(isset($this->lastKiller[$sender->getName()])) {
                                            $this->sendInvite($sender->getName());
                                            return true;
                                        } else {
                                            $sender->sendMessage(Color::RED."You must have a killer to do this.");
                                            return true;
                                        }
                                    case 1:
                                        if(strtolower($args[0]) == 'accept') {
                                            if($this->checkRequests($sender->getName())) {
                                                $this->startDuel($sender->getName());
                                                return true;
                                            } else {
                                                $sender->sendMessage(Color::RED."You have no requests.");
                                                return true;
                                            }
                                        }
                                        if(strtolower($args[0]) == 'deny') {
                                            if($this->checkRequests($sender->getName())) {
                                                $this->sendDeny($sender->getName());
                                                return true;
                                            } else {
                                                $sender->sendMessage(Color::RED."You have no requests.");
                                                return true;
                                            }
                                        }
                                        if(strtolower($args[0]) == 'stats') {
                                            $this->sendStats($sender->getName());
                                            return true;
                                        }
                                        if(strtolower($args[0]) == 'help') {
                                            $this->sendHelp($sender);
                                            return true;
                                        }
                                        $sender->sendMessage(Color::RED."That subcommand is invalid. /revenge help");
                                        return;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    public function isConfigSet() {
        if($this->getConfig()->getNested("sessionInfo.sessionTime") !== null) {
            if($this->getConfig()->getNested("sessionInfo.otherPlayerInvisible") !== null) {
                if($this->getConfig()->getNested("sessionInfo.mainPlayersInvisibleToOthers") !== null) {
                    if($this->getConfig()->getNested("sessionInfo.punishOnLeave") !== null) {
                        if($this->getConfig()->getNested("sessionInfo.sessionLives") !== null) {
                            if($this->getConfig()->getNested("sessionInfo.blockAlCommands") !== null) {
                                if($this->getConfig()->getNested("arenaInfo.world") !== null) {
                                    if($this->getConfig()->getNested("arenaInfo.worldX") !== null) {
                                        if($this->getConfig()->getNested("arenaInfo.worldY") !== null) {
                                            if($this->getConfig()->getNested("arenaInfo.worldZ") !== null) {
                                                return true;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    public function sendInvite($player) {
        
    }
}

