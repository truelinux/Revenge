<?php
namespace Revenge;

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;


class EndGame extends PluginTask {
    
    public function __construct(Main $plugin, Player $p1, Player $p2) {
        $this->plugin = $plugin;
        $this->p1 = $p1;
        $this->p2 = $p2;
        parent::__construct ( $plugin );
    }
    
    public function onRun($currentTick) {
        if(!isset($this->plugin->busy[$this->p1->getName()])) {
            return;
        }
        $this->p1->sendMessage(Color::DARK_PURPLE."Duel Ending...");
        $this->p2->sendMessage(Color::DARK_PURPLE."Duel Ending...");
        unset($this->busy[$this->p1->getName()]);
        unset($this->busy[$this->p2->getName()]);
        $this->p1->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
        $this->p2->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
        foreach($this->plugin->getServer()->getOnlinePlayers() as $p) {
            if($p == $this->p1) {
                $p->spawnToAll();
            }
            if($p == $this->p2) {
                $p->spawnToAll();
            }
            $p->spawnTo($this->p1);
            $p->spawnTo($this->p2);
        }
        return;
    }
}

