<?php
namespace SalmonDE\TopVoter;

use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use SalmonDE\TopVoter\Tasks\UpdateVotesTask;

class TopVoter extends PluginBase
{

    private $eventListener = null;

    private $voters = [];
    private $particle = null;
    public $worlds = [];

    public function onEnable(){
        $this->saveResource('config.yml');
        $this->initParticle();
        $this->worlds = (array) $this->getConfig()->get('Worlds');
        $this->getServer()->getScheduler()->scheduleRepeatingTask($this->updateTask = new UpdateVotesTask($this), (($iv = $this->getConfig()->get('Update-Interval')) >= 180 ? $iv : 180) * 20);

        $this->eventListener = $this->eventListener ?? new EventListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->eventListener, $this);
    }

    private function initParticle(){
        if(!$this->particle instanceof FloatingTextParticle){
            $pos = $this->getConfig()->get('Pos');
            $this->particle = new FloatingTextParticle(new Vector3($pos['X'], $pos['Y'], $pos['Z']), '', TF::DARK_GREEN.TF::BOLD.$this->getConfig()->get('Header'));
        }
    }

    public function sendParticle(array $players = null, bool $force = false){
        $this->particle->setInvisible(false);

        if($players === null){
            $players = $this->getServer()->getOnlinePlayers();
        }

        foreach($players as $player){
            if($force || in_array($player->getLevel()->getName(), $this->worlds)){
                $player->getLevel()->addParticle($this->particle, [$player]);
            }
        }
    }

    public function removeParticle(array $players = null){
        $this->particle->setInvisible();

        if($players === null){
            $players = $this->getServer()->getOnlinePlayers();
        }

        foreach($players as $player){
            $player->getLevel()->addParticle($this->particle, [$player]);
        }
    }

    public function updateParticle() : string{
        $text = '';

        foreach($this->voters as $voter){
            $text .= "\n".TF::GOLD.str_replace(['{player}', '{votes}'], [$voter['nickname'], $voter['votes']], $this->getConfig()->get('Text')).TF::RESET;
        }

        $this->particle->setText($text);
        return $text;
    }

    public function setVoters(array $voters){
        $this->voters = $voters;
    }

    public function getVoters() : array{
        return $this->voters;
    }
}
