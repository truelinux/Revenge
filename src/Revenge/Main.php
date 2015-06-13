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
    
    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->db = new \SQLite3($this->getDataFolder() . "Stats.db");
        $this->getConfig()->setNested("sessionInfo.sessionTime", 60);
        $this->getConfig()->setNested("sessionInfo.otherPlayersInvisible", 'true');
        $this->getConfig()->setNested("sessionInfo.mainPlayersInvisibleToOthers", 'true');
        $this->getConfig()->setNested("sessionInfo.punishOnLeave", 'true');
        $this->getConfig()->setNested("sessionInfo.blockAllCommands", 'true');
        $this->getConfig()->setNested("arenaInfo.worldName", null);
        $this->getConfig()->setNested("arenaInfo.posX", null);
        $this->getConfig()->setNested("arenaInfo.posX", null);
        $this->getConfig()->setNested("arenaInfo.posX", null);
        $this->isConfigSet();
        $this->punishsaved = new Config($this->getDataFolder(). "PendingPunishments.yml", Config::YAML);
        $this->punish = $this->punishsaved->getAll();
        $this->db->exec("CREATE TABLE IF NOT EXISTS stats (player VARCHAR, battles INTEGER, won INTEGER, loss INTEGER);");
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
                                            $this->sendInvite($sender);
                                            return true;
                                        } else {
                                            $sender->sendMessage(Color::RED."You must have a killer to do this.");
                                            return true;
                                        }
                                    case 1:
                                        if(strtolower($args[0]) == 'accept') {
                                            if($this->checkRequests($sender)) {
                                                $this->startDuel($sender);
                                                return true;
                                            } else {
                                                $sender->sendMessage(Color::RED."You have no requests.");
                                                return true;
                                            }
                                        }
                                        if(strtolower($args[0]) == 'deny') {
                                            if($this->checkRequests($sender)) {
                                                $this->sendDeny($sender);
                                                return true;
                                            } else {
                                                $sender->sendMessage(Color::RED."You have no requests.");
                                                return true;
                                            }
                                        }
                                        if(strtolower($args[0]) == 'stats') {
                                            $this->sendStats($sender);
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
    
    //======================================CONFIGCHECK===================================\\
    
    public function isConfigSet() {
        if($this->getConfig()->getNested("sessionInfo.sessionTime") !== null) {
            if($this->getConfig()->getNested("sessionInfo.otherPlayersInvisible") !== null) {
                if($this->getConfig()->getNested("sessionInfo.mainPlayersInvisibleToOthers") !== null) {
                    if($this->getConfig()->getNested("sessionInfo.punishOnLeave") !== null) {
                        if($this->getConfig()->getNested("sessionInfo.sessionLives") !== null) {
                            if($this->getConfig()->getNested("sessionInfo.blockAlCommands") !== null) {
                                if($this->getConfig()->getNested("arenaInfo.worldName") !== null) {
                                    if($this->getConfig()->getNested("arenaInfo.posX") !== null) {
                                        if($this->getConfig()->getNested("arenaInfo.posY") !== null) {
                                            if($this->getConfig()->getNested("arenaInfo.posZ") !== null) {
                                                return true;
                                            } else {
                                                $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                                $this->getServer()->getPluginManager()->disablePlugin($this);
                                                return;
                                            }
                                        } else {
                                            $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                            $this->getServer()->getPluginManager()->disablePlugin($this);
                                            return;
                                        }
                                    } else {
                                        $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                        $this->getServer()->getPluginManager()->disablePlugin($this);
                                        return;
                                    }
                                } else {
                                    $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                    $this->getServer()->getPluginManager()->disablePlugin($this);
                                    return;
                                }
                            } else {
                                $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                                $this->getServer()->getPluginManager()->disablePlugin($this);
                                return;
                            }
                        } else {
                            $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                            $this->getServer()->getPluginManager()->disablePlugin($this);
                            return;
                        }
                    } else {
                        $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                        $this->getServer()->getPluginManager()->disablePlugin($this);
                        return;
                    }
                } else {
                    $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                    $this->getServer()->getPluginManager()->disablePlugin($this);
                    return;
                }
            } else {
                $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        } else {
            $this->getLogger()->error("ERROR: Please configure the plugin correctly. Disabling Plugin!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
    }
    
    //======================================SENDINVITE===================================\\
    
    public function sendInvite(Player $player) {
        $name = $player->getName();
        $killer = $this->lastKiller[$name];
        $this->invites[$name] = $this->lastKiller[$name];
        $player->sendMessage(Color::GRAY."> Request sent to".Color::RED."$killer");
        $killer = $this->getServer()->getPlayer($killer);
        $killer->sendMessage(Color::GRAY."> ".Color::RED."$name ".Color::GRAY."- 1v1 Battle Request\n'/revenge accept' to accept request");
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
        $player->sendMessage(Color::GRAY."> Request sent denied.");
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
        $world = $this->getConfig()->getNested("arenaInfo.worldName");
        $x = $this->getConfig()->getNested("arenaInfo.posX");
        $y = $this->getConfig()->getNested("arenaInfo.posY");
        $z = $this->getConfig()->getNested("arenaInfo.posZ");
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
        $stat = $this->db->query("SELECT * FROM stats WHERE player='$p1';");
        $result = $stat->fetchArray(SQLITE3_ASSOC);
        if(empty($result)) {
            $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (player, battles, won, loss) VALUES (:player, :battles, :won, :loss);");
            $ins->bindValue(":player", $p1);
            $ins->bindValue(":battles", 0);
            $ins->bindValue(":won", 0);
            $ins->bindValue(":loss", 0);
            $result = $ins->execute();
        }
        if(!empty($result)) {
            $pbattles = $result["battles"];
            $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (battles) VALUES (:battles);");
            $ins->bindValue(":battles", $pbattles + 1);
            $result = $ins->execute();
        }
        $stat = $this->db->query("SELECT * FROM stats WHERE player='$p2';");
        $result = $stat->fetchArray(SQLITE3_ASSOC);
        if(empty($result)) {
            $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (player, battles, won, loss) VALUES (:player, :battles, :won, :loss);");
            $ins->bindValue(":player", $p2);
            $ins->bindValue(":battles", 0);
            $ins->bindValue(":won", 0);
            $ins->bindValue(":loss", 0);
            $result = $ins->execute();
        }
        if(!empty($result)) {
            $pbattles = $result["battles"];
            $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (battles) VALUES (:battles);");
            $ins->bindValue(":battles", $pbattles + 1);
            $result = $ins->execute();
        }
        $end = $this->getConfig()->getNested("sessionInfo.sessionTime");
        $this->getServer()->getScheduler()->scheduleDelayedTask(new EndGame($this, $p1, $p2), $end*20);
        return;
    }
    
    //======================================STATS===================================\\
    
    public function sendStats(Player $player) {
        $name = $player->getName();
        $stat = $this->db->query("SELECT * FROM stats WHERE player='$name';");
        $result = $stat->fetchArray(SQLITE3_ASSOC);
        if(empty($result)) {
            $player->sendMessage(Color::RED."You have no stats. Noob");
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
            $player->kill();
            $this->getServer()->broadcastMessage(Color::GRAY."> ".Color::GOLD."$name ".Color::RED."was punished for leaving while in a 1v1.");
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
    
    public function onCommandProcess(PlayerCommandPreprocessEventEvent $event) {
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
                $p2 = $this->getServer()->getPlayer($this->busy[$name]);
                $name2 = $p2->getName();
                $player->teleport($this->getServer()->getDefaultLevel()->getSpawnLocation());
                $p2->teleport($this->getServer()->getDefaultLevel()->getSpawnLocation());
                $this->getServer()->broadcastMessage(Color::GRAY."> ".Color::GOLD."$name2 ".Color::DARK_PURPLE."won the 1v1 battle against $name");
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
                $loss = $result["loss"];
                $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (loss) VALUES (:loss);");
                $ins->bindValue(":loss", $loss + 1);
                $result = $ins->execute();
                $stat = $this->db->query("SELECT * FROM stats WHERE player='$name2';");
                $result = $stat->fetchArray(SQLITE3_ASSOC);
                $loss = $result["won"];
                $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (won) VALUES (:won);");
                $ins->bindValue(":won", $won + 1);
                $result = $ins->execute();
                return true;
            }
        }
    }
    
    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if(isset($this->busy[$name])) {
            if($this->getConfig()->getNested("sessionInfo.punishOnLeave") == "true") {
                $this->punish[$name] = "TRUE";
            }
            $name2 = $this->busy[$name];
            $stat = $this->db->query("SELECT * FROM stats WHERE player='$name';");
            $result = $stat->fetchArray(SQLITE3_ASSOC);
            $loss = $result["loss"];
            $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (loss) VALUES (:loss);");
            $ins->bindValue(":loss", $loss + 1);
            $result = $ins->execute();
            $stat = $this->db->query("SELECT * FROM stats WHERE player='$name2';");
            $result = $stat->fetchArray(SQLITE3_ASSOC);
            $loss = $result["won"];
            $ins = $this->db->prepare("INSERT OR REPLACE INTO stats (won) VALUES (:won);");
            $ins->bindValue(":won", $won + 1);
            $result = $ins->execute();
            $p2 = $this->getServer()->getPlayer($name2);
            $p2->teleport($this->getServer()->getDefaultLevel()->getSpawnLocation());
            foreach($this->getServer()->getOnlinePlayers() as $p) {
                if($p == $p2) {
                    $p->spawnToAll();
                }
                $p->spawnTo($p2);
            }
            $this->getServer()->broadcastMessage(Color::GRAY."> ".Color::GOLD."$name2 ".Color::DARK_PURPLE."won the 1v1 battle against $name");
            unset($this->busy[$this->busy[$name]]);
            unset($this->busy[$name]);
            return;
        }
    }
    
    public function onDisable() {
        $this->punishsaved->setAll($this->punish);
        $this->db->close;
        return;
    }
}

