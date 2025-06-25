<?php

/*
__PocketMine Plugin__
name=Vanish
description=Позволяет скрыть своего персонажа из игры для других игроков.
version=1.0.0
author=OleksandrVovchenko
class=Vanish
apiversion=12.1,12.2
*/

class Vanish implements Plugin {
    private $api;
    private $vanishedPlayers = [];

    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
    }

    public function init(){
        $this->api->console->register("vanish", "Toggle your vanish status.", array($this, "onVanishCommand"));
        $this->api->addHandler("player.join", array($this, "onPlayerJoin"));
        $this->api->addHandler("player.quit", array($this, "onPlayerQuit"));
    }

    public function onVanishCommand($cmd, $args, $issuer){
        if(!($issuer instanceof Player)) {
            return "This command can only be used in-game.";
        }

        $username = strtolower($issuer->username);

        if (isset($this->vanishedPlayers[$username])) {
            unset($this->vanishedPlayers[$username]);
            $this->api->player->spawnToAllPlayers($issuer); 
            $issuer->sendChat("Вы больше не в режиме невидимости.");
        } else {
            $this->vanishedPlayers[$username] = true;
            $pk = new RemoveEntityPacket();
            $pk->eid = $issuer->eid;
            
            foreach($this->api->player->getAll() as $p){
                if(strtolower($p->username) !== $username){
                    $p->dataPacket($pk);
                }
            }
            $issuer->sendChat("Вы теперь в режиме невидимости.");
        }
        return true;
    }

    public function onPlayerJoin(Player $newPlayer, $event){
        
        foreach ($this->vanishedPlayers as $vanishedUsername => $value) {
            $vanishedPlayer = null;
            foreach ($this->api->player->getAll() as $p) {
                if (strtolower($p->username) === $vanishedUsername) {
                    $vanishedPlayer = $p;
                    break;
                }
            }

            if ($vanishedPlayer instanceof Player) {
                $pk = new RemoveEntityPacket();
                $pk->eid = $vanishedPlayer->eid;
                $newPlayer->dataPacket($pk); 
            }
        }
    }

    public function onPlayerQuit(Player $player, $event){
        $username = strtolower($player->username);
        if (isset($this->vanishedPlayers[$username])) {
            unset($this->vanishedPlayers[$username]);
        }
    }

    public function __destruct(){}
}
