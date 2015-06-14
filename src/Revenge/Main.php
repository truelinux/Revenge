<?php
namespace Revenge;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\utils\TextFormat as Color;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class Main extends PluginBase implements Listener {
    
    public $busy = array();
    public $lastKiller = array();
    public $invites = array();
    public $standby = array();
    public $punish = array();
    public $punishsaved;
    public $config;
    
    public function onEnable() {
        $this->getLogger()->info(Color::GREEN."Revenge - Loading...\nLoading...\nLoading...");
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->punishsaved = new Config($this->getDataFolder(). "PendingPunishments.yml", Config::YAML);
        $this->db = new \SQLite3($this->getDataFolder() . "Stats.db");
        $this->getConfig()->save();
        $this->isConfigSet();
        $this->getLogger()->info(Color::GREEN."Revenge - All configurations and databases loaded.");
        $this->punish = $this->punishsaved->getAll();
        $this->db->exec("CREATE TABLE IF NOT EXISTS stats (player VARCHAR, battles INTEGER, won INTEGER, loss INTEGER);");
        $this->getLogger()->info(Color::GREEN."Revenge - Enabled");
        return;
    }
    
    public function getAll(){
        $resule = [];
        foreach(scandir($this->getDataFolder()) as $file){
            if($file === "config.yml"){
                $data = @yaml_parse(Config::fixYAMLIndexes(@file_get_contents($this->getDataFolder().$file)));
                $resule[] = $data;
            }
        }
        return $resule;
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        $file = $this->getAll();
        if($sender instanceof Player) {
            $level = $sender->getLevel()->getName();
            if($this->getConfig()->get("Enabled") == true) {
                foreach($this->getConfig()->get("enabledWorlds") as $world) {
                    if($world == $level) {
                        $test = "enabled";
                    }
                    if(!$test == "enabled") {
                        $sender->sendMessage(Color::RED."> You may not use this command here.");
                        return;
                    }
                    if(!isset($this->busy[$sender->getName()])) {
                        if(strtolower($command->getName()) == "revenge") {
                            switch (count($args)) {
                                case 0:
                                    if(isset($this->lastKiller[$sender->getName()])) {
                                        $this->sendInvite($sender);
                                        return true;
                                    } else {
                                        $sender->sendMessage(Color::RED."> You must have a killer to do this.");
                                        return true;
                                    }
                                    break;
                                case 1:
                                    if(strtolower($args[0]) == 'accept') {
                                        if($this->checkRequests($sender)) {
                                            $this->startDuel($sender);
                                            return true;
                                        } else {
                                            $sender->sendMessage(Color::RED."> You have no requests.");
                                            return true;
                                        }
                                    }
                                    if(strtolower($args[0]) == 'deny') {
                                        if($this->checkRequests($sender)) {
                                            $this->sendDeny($sender);
                                            return true;
                                        } else {
                                            $sender->sendMessage(Color::RED."> You have no requests.");
                                            return true;
                                        }
                                    }
                                    if(strtolower($args[0]) == 'stats') {
                                        $this->sendStats($sender);
                                        return true;
                                    }
                                    if(strtolower($args[0]) == 'help') {
                                        $sender->sendMessage(Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-\n".
                                        Color::RED."/revenge\n".
                                        Color::RED."/revenge accept\n".
                                        Color::RED."/revenge deny\n".
                                        Color::RED."/revenge help\n".
                                        Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-\n");
                                        return;
                                    }
                                    $sender->sendMessage(Color::RED."> That subcommand is invalid. /revenge help");
                                    return;
                                    break;
                            }
                        }
                    }else {
                        $sender->sendMessage(Color::RED."> You can not run the command while in a duel!");
                        return;
                    }
                }
            }else {
                $sender->sendMessage(Color::RED."> You are not permitted to do that!");
                return;
            }
        }else {
            $sender->sendMessage(Color::RED."> You are not permitted to do that!");
            return;
        }
    }
    
    //======================================CONFIGCHECK===================================\\
    
    public function isConfigSet() {
        foreach($this->getAll() as $file) {
            if($file["Session"]["Time"] !== null) {
                if($file["Session"]["otherPlayersInvisible"] !== null) {
                    if($file["Session"]["mainPlayersInvisibleToOthers"] !== null) {
                        if($file["Session"]["punishOnLeave"] !== null) {
                            if($file["Session"]["blockAllCommands"] !== null) {
                                if($file["Arena"]["World"] !== null) {
                                    if($file["Arena"]["X"] !== null) {
                                        if($file["Arena"]["Y"] !== null) {
                                            if($file["Arena"]["Z"] !== null) {
                                                return;
                                            } else {
                                                $this->punishsaved->setAll($this->punish);
                                                $this->punishsaved->save();
                                                $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                                $this->getServer()->getPluginManager()->disablePlugin($this);
                                                return "TRUE";
                                            }
                                        } else {
                                            $this->punishsaved->setAll($this->punish);
                                                $this->punishsaved->save();
                                            $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                            $this->getServer()->getPluginManager()->disablePlugin($this);
                                            return;
                                        }
                                    } else {
                                        $this->punishsaved->setAll($this->punish);
                                                $this->punishsaved->save();
                                        $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                        $this->getServer()->getPluginManager()->disablePlugin($this);
                                        return;
                                    }
                                } else {
                                    $this->punishsaved->setAll($this->punish);
                                                $this->punishsaved->save();
                                    $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                    $this->getServer()->getPluginManager()->disablePlugin($this);
                                    return;
                                }
                            } else {
                                $this->punishsaved->setAll($this->punish);
                                                $this->punishsaved->save();
                                $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                $this->getServer()->getPluginManager()->disablePlugin($this);
                                return;
                            }
                        } else {
                            $this->punishsaved->setAll($this->punish);
                            $this->punishsaved->save();
                            $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                            $this->getServer()->getPluginManager()->disablePlugin($this);
                            return;
                        }
                    } else {
                        $this->punishsaved->setAll($this->punish);
                                                $this->punishsaved->save();
                        $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                        $this->getServer()->getPluginManager()->disablePlugin($this);
                        return;
                    }
                } else {
                    $this->punishsaved->setAll($this->punish);
                                                $this->punishsaved->save();
                    $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                    $this->getServer()->getPluginManager()->disablePlugin($this);
                    return;
                }
            } else {
                $this->punishsaved->setAll($this->punish);
                                                $this->punishsaved->save();
                $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }
    }
    
    //======================================SENDINVITE===================================\\
    
    public function sendInvite(Player $player) {
        $name = $player->getName();
        $killer = $this->lastKiller[$name];
        $this->invites[$name] = $this->lastKiller[$name];
        $player->sendMessage(Color::GRAY."> Request sent to ".Color::BOLD.Color::RED."$killer");
        $killer = $this->getServer()->getPlayer($killer);
        $killer->sendMessage(Color::GRAY."> ".Color::BOLD.Color::RED."$name ".Color::GRAY."- 1v1 Battle Request\n".Color::GRAY."'/revenge accept' to accept request");
        return;
    }
    
    //======================================REQUESTS===================================\\
    
    public function checkRequests(Player $player) {
        $name = $player->getName();
        foreach($this->invites as $p) {
            if($p == $name) {
                return true;
            }
        }
        return false;
    }
    
    //======================================DENY===================================\\
    
    public function sendDeny(Player $player) {
        $name = $player->getName();
        foreach($this->invites as $r => $p) {
            if($p == $name) {
                $requester = $this->getServer()->getPlayer($r);
                $requester->sendMessage(Color::GRAY."> Request denied - ".Color::BOLD.Color::RED."$name");
                unset($this->invites[$r]);
            }
        }
        $player->sendMessage(Color::GRAY."> Request denied.");
        return;
    }
    
    //======================================STARTDUEL===================================\\
    
    public function startDuel(Player $player) {
        $name1 = $player->getName();
        foreach($this->invites as $r => $p) {
            if($p == $name1) {
                $name2 = $r;
            }
        }
        $p1 = $player;
        $p2 = $this->getServer()->getPlayer($name2);
        foreach($this->getAll() as $file) {
            $world = $file["Arena"]["World"];
            $x = $file["Arena"]["X"];
            $y = $file["Arena"]["Y"];
            $z = $file["Arena"]["Z"];
        }
        if($this->getServer()->isLevelLoaded($world)) {
            $this->getServer()->getLevelByName($world)->loadChunk($x, $z);
        } 
        elseif($this->getServer()->isLevelLoaded($world)) {
            $this->getLogger()->error("ERROR: Plugin -> Revenge | CODE:156 | WORLD NOT LOADED: $world");
            $this->getServer()->broadcastMessage(Color::RED."ERROR: Plugin -> Revenge | CODE:156 | WORLD NOT LOADED: $world");
            return;
        }
        $pos = new Vector3($x, $y, $z);
        $pos2 = new Vector3($x + 5, $y, $z + 5);
        $p1->teleport($this->getServer()->getLevelByName($world)->getSpawnLocation());
        $p1->teleport($pos);
        $p2->teleport($this->getServer()->getLevelByName($world)->getSpawnLocation());
        $p2->teleport($pos2);
        $p1->despawnFromAll();
        $p2->despawnFromAll();
        foreach($this->getServer()->getOnlinePlayers() as $p) {
            if($p == $p1) {
                $p->spawnTo($p2);
            }
            if($p == $p2) {
                $p->spawnTo($p1);
            }
            $p->despawnFrom($p1);
            $p->despawnFrom($p2);
        }
        unset($this->invites[$name2]);
        $this->busy[$name1] = $name2;
        $this->busy[$name2] = $name1;
        $this->standby[$name1] = "TRUE";
        $this->standby[$name2] = "TRUE";
        $this->getServer()->getScheduler()->scheduleDelayedTask(new Timer($this, $p1, $p2, 5), 5*20);
        $this->getServer()->getScheduler()->scheduleDelayedTask(new Timer($this, $p1, $p2, 4), 6*20);
        $this->getServer()->getScheduler()->scheduleDelayedTask(new Timer($this, $p1, $p2, 3), 7*20);
        $this->getServer()->getScheduler()->scheduleDelayedTask(new Timer($this, $p1, $p2, 2), 8*20);
        $this->getServer()->getScheduler()->scheduleDelayedTask(new Timer($this, $p1, $p2, 1), 9*20);
        $stat = $this->db->query("SELECT * FROM stats WHERE player='$name1';");
        $result = $stat->fetchArray(SQLITE3_ASSOC);
        if(!empty($result)) {
            $pbattles = $result["battles"] + 1;
            $ins = $this->db->prepare("UPDATE stats SET battles='$pbattles' WHERE player='$name1';");
            $send = $ins->execute();
        }
        if(empty($result)) {
            $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (player, battles, won, loss) VALUES (:player, :battles, :won, :loss);");
            $ins->bindValue(":player", $name1);
            $ins->bindValue(":battles", 1);
            $ins->bindValue(":won", 0);
            $ins->bindValue(":loss", 0);
            $send = $ins->execute();
        }
        $stat = $this->db->query("SELECT * FROM stats WHERE player='$name2';");
        $result = $stat->fetchArray(SQLITE3_ASSOC);
        if(!empty($result)) {
            $pbattles = $result["battles"] + 1;
            $ins = $this->db->prepare("UPDATE stats SET battles='$pbattles' WHERE player='$name2';");
            $send = $ins->execute();
        }
        if(empty($result)) {
            $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (player, battles, won, loss) VALUES (:player, :battles, :won, :loss);");
            $ins->bindValue(":player", $name2);
            $ins->bindValue(":battles", 1);
            $ins->bindValue(":won", 0);
            $ins->bindValue(":loss", 0);
            $send = $ins->execute();
        }
        foreach($this->getAll() as $file) {
            $end = $file["Session"]["Time"];
        }
        $this->getServer()->getScheduler()->scheduleDelayedTask(new EndGame($this, $p1, $p2), $end*20);
        return;
    }
    
    //======================================STATS===================================\\
    
    public function sendStats(Player $player) {
        $name = $player->getName();
        $stat = $this->db->query("SELECT * FROM stats WHERE player='$name';");
        $result = $stat->fetchArray(SQLITE3_ASSOC);
        if(empty($result)) {
            $player->sendMessage(Color::RED."> You have no stats. Noob");
            return;
        }
        $battles = $result["battles"];
        $won = $result["won"];
        $loss = $result["loss"];
        $player->sendMessage(Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-\n".
        Color::GOLD."Battles: ".Color::RED."$battles\n".
        Color::GOLD."Wins: ".Color::RED."$won\n".
        Color::GOLD."Loss: ".Color::RED."$loss\n".
        Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-".Color::RED."-".Color::GOLD."-\n");
        return;
    }
    
    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if(isset($this->punish[$name])) {
            $this->getServer()->getScheduler()->scheduleDelayedTask(new KillTask($this, $player), 40);
            $this->getServer()->broadcastMessage(Color::GRAY."> ".Color::GOLD.Color::BOLD."$name ".Color::RED."was punished for leaving while in a 1v1.");
            return;
        }
    }
    
    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if(isset($this->standby[$name])) {
            $event->setCancelled();
            $player->sendTip(Color::GRAY."Duel Has Not Started Yet!");
            return;
        }
    }
    
    public function onCommandProcess(PlayerCommandPreprocessEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if(isset($this->busy[$name])) {
            $event->setCancelled();
            $player->sendTip(Color::RED."You cant do that here!");
            return;
        }
    }
    
    public function onDeath(PlayerDeathEvent $event) {
        $player = $event->getEntity();
        $name = $player->getName();
        if($this->getConfig()->get("Enabled")) {
            $cause = $player->getLastDamageCause();
            if($cause instanceof EntityDamageByEntityEvent) {
                $damger = $cause->getDamager();
                if($damger instanceof Player) {
                    $damager = $damger->getName();
                    $this->lastKiller[$name] = $damager;
                }
            } 
            if(isset($this->busy[$name])) {
                $player->sendMessage(Color::RED."> Do '/revenge' to start a 1v1 duel with your killer.");
                $p2 = $this->getServer()->getPlayer($this->busy[$name]);
                $name2 = $p2->getName();
                $player->teleport($this->getServer()->getDefaultLevel()->getSpawnLocation());
                $p2->teleport($this->getServer()->getDefaultLevel()->getSpawnLocation());
                $p2->sendPopup(Color::GOLD."+1 Win");
                $p2->sendMessage(Color::RED."> Do '/revenge stats' to see your progress!");
                $this->getServer()->broadcastMessage(Color::GRAY."> ".Color::GOLD.Color::BOLD."$name2 ".Color::RESET.Color::DARK_PURPLE."won the 1v1 battle against $name!");
                foreach($this->getServer()->getOnlinePlayers() as $p) {
                    if($p == $player) {
                        $p->spawnToAll();
                    }
                    if($p == $p2) {
                        $p->spawnToAll();
                    }
                    $p->spawnTo($player);
                    $p->spawnTo($p2);
                }
                $stat = $this->db->query("SELECT * FROM stats WHERE player='$name';");
                $result = $stat->fetchArray(SQLITE3_ASSOC);
                $loss = $result["loss"] + 1;
                $ins = $this->db->prepare("UPDATE stats SET loss='$loss' WHERE player='$name';");
                $result = $ins->execute();
                $stat = $this->db->query("SELECT * FROM stats WHERE player='$name2';");
                $result = $stat->fetchArray(SQLITE3_ASSOC);
                $won = $result["won"] + 1;
                $ins = $this->db->prepare("UPDATE stats SET won='$won' WHERE player='$name2';");
                $result = $ins->execute();
                unset($this->busy[$this->busy[$name]]);
                unset($this->busy[$name]);
                return true;
            }
        }
    }
    
    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if(isset($this->busy[$name])) {
            foreach($this->getAll() as $file) {
                if($file["Session"]["punishOnLeave"] == true) {
                    $this->punish[$name] = "TRUE";
                    $this->punishsaved->setAll($this->punish);
                }
            }
            $name2 = $this->busy[$name];
            $stat = $this->db->query("SELECT * FROM stats WHERE player='$name';");
            $result = $stat->fetchArray(SQLITE3_ASSOC);
            $loss = $result["loss"] + 1;
            $ins = $this->db->prepare("UPDATE stats SET loss='$loss' WHERE player='$name';");
            $result = $ins->execute();
            $stat = $this->db->query("SELECT * FROM stats WHERE player='$name2';");
            $result = $stat->fetchArray(SQLITE3_ASSOC);
            $won = $result["won"] + 1;
            $ins = $this->db->prepare("UPDATE stats SET won='$won' WHERE player='$name2';");
            $result = $ins->execute();
            $p2 = $this->getServer()->getPlayer($name2);
            $p2->teleport($this->getServer()->getDefaultLevel()->getSpawnLocation());
            foreach($this->getServer()->getOnlinePlayers() as $p) {
                if($p == $p2) {
                    $p->spawnToAll();
                }
                $p->spawnTo($p2);
            }
            $this->getServer()->broadcastMessage(Color::GRAY."> ".Color::GOLD.Color::BOLD."$name2 ".Color::RESET.Color::DARK_PURPLE."won the 1v1 battle against $name!");
            unset($this->busy[$this->busy[$name]]);
            unset($this->busy[$name]);
            return;
        }
    }
   
}

