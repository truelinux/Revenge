<?php
namespace Revenge;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;


class KillTask extends PluginTask {
    
    public function __construct(Main $plugin, Player $p) {
        parent::__construct ( $plugin );
        $this->plugin = $plugin;
        $this->player = $p;

    }
    
    public function onRun($currentTick) {
        $this->player->setHealth(0);
        return;
    }
}

