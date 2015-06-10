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
            if($this->getConfig()->get("Enabled") == true) {
                if($this->getConfig()->getNested("enabledWorlds.$level") === 'enabled') {
                    if(!isset($this->busy[$sender->getName()])) {
                        if($thi) {
                            
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
                            if($this->getConfig()->getNested("sessionInfo.") !== null) {
                                
                            }
                        }
                    }
                }
            }
        }
    }
}

