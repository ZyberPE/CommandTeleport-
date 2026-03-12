<?php

namespace CommandTeleport;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class Main extends PluginBase implements Listener{

    private array $teleportCommands = [];

    public function onEnable(): void{
        $this->saveDefaultConfig();
        $this->loadCommands();
    }

    private function loadCommands(): void{
        $config = $this->getConfig()->get("commands", []);

        foreach($config as $cmd => $data){
            $command = new class($cmd, $data["description"] ?? "", $data["permission"] ?? "", $this) extends Command{

                private $plugin;
                private $data;

                public function __construct(string $name, string $description, string $permission, Main $plugin){
                    parent::__construct($name, $description);
                    $this->setPermission($permission);
                    $this->plugin = $plugin;
                    $this->data = $plugin->getConfig()->get("commands")[$name];
                }

                public function execute(CommandSender $sender, string $label, array $args): bool{

                    if(!$sender instanceof Player){
                        $sender->sendMessage("Run this command in game.");
                        return true;
                    }

                    if(!$this->testPermission($sender)){
                        return true;
                    }

                    $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($this->data["world"]);

                    if($world === null){
                        $sender->sendMessage("§cWorld not found.");
                        return true;
                    }

                    $pos = new Position(
                        (float)$this->data["x"],
                        (float)$this->data["y"],
                        (float)$this->data["z"],
                        $world
                    );

                    $sender->teleport($pos);
                    $sender->sendMessage($this->data["message"] ?? "§aTeleported!");

                    return true;
                }
            };

            $this->getServer()->getCommandMap()->register("commandteleport", $command);
        }
    }
}
