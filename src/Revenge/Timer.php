<?php
namespace Revenge;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;


class Timer extends PluginTask {
    
    public function __construct(Main $plugin, Player $p1, Player $p2, $time = null) {
        $this->plugin = $plugin;
        $this->time = $time;
        $this->p1 = $p1;
        $this->p2 = $p2;
        parent::__construct ( $plugin );
    }
    
    public function onRun($currentTick) {
        $time = $this->time;
        $this->pl->sendMessage(Color::DARK_PURPLE."$time...");
        $this->p2->sendMessage(Color::DARK_PURPLE."$time...");
        if($time == 1) {
            $this->p1->sendMessage(Color::RED.Color::BOLD."------BEGIN------");
            $this->p2->sendMessage(Color::RED.Color::BOLD."------BEGIN------");
            unset($this->plugin->standby[$p1->getName()]);
            unset($this->plugin->standby[$p2->getName()]);
        }
        return;
    }
}

