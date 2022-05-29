<?php
declare(strict_types=1);

namespace EasterEgg;

use EasterEgg\event\FindEasterEggEvent;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use function count;

class EasterEgg{

	protected string $name;

	/** @var Item[] */
	protected array $items = [];

	protected array $players = [];

	protected int $x;

	protected int $y;

	protected int $z;

	protected string $world;

	protected int $money = 0;

	public function __construct(string $name, array $items, array $players, int $x, int $y, int $z, string $world, int $money){
		$this->name = $name;
		foreach($items as $item)
			$this->items[] = Item::jsonDeserialize($item);
		$this->players = $players;

		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->world = $world;
		$this->money = $money;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getPosition() : Position{
		return new Position($this->x, $this->y, $this->z, Server::getInstance()->getWorldManager()->getWorldByName($this->world));
	}

	public function getItems() : array{
		return $this->items;
	}

	public function getPlayers() : array{
		return $this->players;
	}

	public function getMoney() : int{
		return $this->money;
	}

	public function equals(EasterEgg $that) : bool{
		return $this->getPosition()->equals($that->getPosition()) && $this->getItems() === $that->getItems() && $this->getPlayers() === $that->getPlayers() && $this->getName() === $that->getName();
	}

	public function addPlayer(Player $player) : void{
		$this->players[$player->getName()] = time();
	}

	public function addItem(Item $item) : void{
		$this->items[] = $item;
	}

	public function setMoney(int $money){
		$this->money = $money;
	}

	public function canComplete(Player $player) : bool{
		//return !isset($this->players[$player->getName()]) or time() >= (($this->players[$player->getName()] ?? 0) + (60 * 60 * 60 * 24));
		if(!isset($this->players[$player->getName()])){
			return true;
		}

		return false;
	}

	public function complete(Player $player) : void{
		$this->addPlayer($player);
		//$player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
		Server::getInstance()->broadcastMessage("§e§l[§fE§e] §f" . $player->getName() . "님이 [ " . $this->getName() . " §f] 을(를) 발견했습니다!");

		$ev = new FindEasterEggEvent($player, $this, $this->items, $this->money);
		$ev->call();

		if(count($ev->getRewards()) > 0){
			foreach($ev->getRewards() as $item)
				$player->getInventory()->addItem($item);
		}

		if($ev->getMoney() > 0)
			EconomyAPI::getInstance()->addMoney($player, $ev->getMoney());
	}

	public static function jsonDeserialize(array $data) : EasterEgg{
		return new EasterEgg((string) $data["name"] ?? "NONE", (array) $data["items"] ?? [], (array) $data["players"] ?? [], (int) $data["x"] ?? 0, (int) $data["y"] ?? 0, (int) $data["z"] ?? 0, (string) $data["world"] ?? "world", (int) $data["money"] ?? 0);
	}

	public function jsonSerialize() : array{
		return [
			"name" => $this->name,
			"items" => array_map(function(Item $item) : array{
				return $item->jsonSerialize();
			}, $this->items),
			"players" => $this->players,
			"x" => $this->x,
			"y" => $this->y,
			"z" => $this->z,
			"world" => $this->world,
			"money" => $this->money
		];
	}
}