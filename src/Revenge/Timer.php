<?php
namespace Revenge;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;


class Timer extends PluginTask {
    
    public function __construct(Main $plugin, Player $p1, Player $p2, $time = null) {
        parent::__construct ( $plugin );
        $this->plugin = $plugin;
        $this->time = $time;
        $this->player1 = $p1;
        $this->player2 = $p2;
    }
    
    public function onRun($currentTick) {
        $time = $this->time;
        $this->player1->sendMessage(Color::DARK_PURPLE."$time...");
        $this->player2->sendMessage(Color::DARK_PURPLE."$time...");
        if($time == 1) {
            $this->player1->sendMessage(Color::RED.Color::BOLD."------BEGIN------");
            $this->player2->sendMessage(Color::RED.Color::BOLD."------BEGIN------");
            unset($this->plugin->standby[$this->player1->getName()]);
            unset($this->plugin->standby[$this->player2->getName()]);
        }
        return;
    }
}

