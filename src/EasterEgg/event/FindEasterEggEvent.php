<?php

declare(strict_types=1);

namespace EasterEgg\event;

use EasterEgg\EasterEgg;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class FindEasterEggEvent extends PlayerEvent{

	protected EasterEgg $easterEgg;

	protected array $rewards = [];

	protected int $money = 0;

	public function __construct(Player $player, EasterEgg $easterEgg, array $rewards, int $money){
		$this->player = $player;
		$this->easterEgg = $easterEgg;
		$this->rewards = $rewards;
		$this->money = $money;
	}

	public function getEasterEgg() : EasterEgg{
		return $this->easterEgg;
	}

	public function getRewards() : array{
		return $this->rewards;
	}

	public function getMoney() : int{
		return $this->money;
	}

	public function setRewards(array $rewards) : void{
		$this->rewards = $rewards;
	}

	public function setMoney(int $money) : void{
		$this->money = $money;
	}
}