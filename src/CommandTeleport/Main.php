<?php

namespace CommandTeleport;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\world\Position;

class Main extends PluginBase{

    public function onEnable(): void{
        $this->saveDefaultConfig();
        $this->registerCommands();
    }

    private function registerCommands(): void{

        $cmds = $this->getConfig()->get("commands", []);
        $permManager = PermissionManager::getInstance();

        foreach($cmds as $name => $data){

            $permission = $data["permission"];

            // Register permission safely
            if($permManager->getPermission($permission) === null){
                $permManager->addPermission(new Permission($permission));
            }

            $command = new class($this, $name, $data) extends Command{

                private Main $plugin;
                private array $data;

                public function __construct(Main $plugin, string $name, array $data){
                    parent::__construct($name, $data["description"] ?? "");
                    $this->plugin = $plugin;
                    $this->data = $data;
                    $this->setPermission($data["permission"]);
                }

                public function execute(CommandSender $sender, string $label, array $args): bool{

                    $config = $this->plugin->getConfig();

                    if(!$sender instanceof Player){
                        $sender->sendMessage($config->get("messages")["console"]);
                        return true;
                    }

                    if(!$sender->hasPermission($this->data["permission"])){
                        $sender->sendMessage($config->get("messages")["no-permission"]);
                        return true;
                    }

                    $worldName = $this->data["world"];
                    $wm = $this->plugin->getServer()->getWorldManager();

                    if(!$wm->isWorldLoaded($worldName)){
                        $wm->loadWorld($worldName);
                    }

                    $world = $wm->getWorldByName($worldName);

                    if($world === null){
                        $sender->sendMessage($config->get("messages")["world-not-found"]);
                        return true;
                    }

                    $pos = new Position(
                        (float)$this->data["x"],
                        (float)$this->data["y"],
                        (float)$this->data["z"],
                        $world
                    );

                    $sender->teleport($pos);
                    $sender->sendMessage($this->data["message"] ?? "§aTeleported.");

                    return true;
                }
            };

            $this->getServer()->getCommandMap()->register("commandteleport", $command);
        }
    }
}
