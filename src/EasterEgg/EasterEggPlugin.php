<?php
declare(strict_types=1);

namespace EasterEgg;

use OnixUtils\OnixUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

class EasterEggPlugin extends PluginBase implements Listener{
	use SingletonTrait;

	/** @var EasterEgg[] */
	protected array $easterEggs = [];

	protected array $mode = [];

	/** @var Config */
	protected Config $config;

	protected array $db = [];

	protected function onLoad() : void{
		self::setInstance($this);
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, []);
		$this->db = $this->config->getAll();

		foreach($this->db as $name => $data){
			$easterEgg = EasterEgg::jsonDeserialize($data);
			$this->easterEggs[$easterEgg->getName()] = $easterEgg;
		}
	}

	protected function onDisable() : void{
		$arr = [];
		foreach($this->getEasterEggs() as $easterEgg){
			$arr[$easterEgg->getName()] = $easterEgg->jsonSerialize();
		}
		$this->config->setAll($arr);
		$this->config->save();
	}

	public function addEasterEgg(EasterEgg $easterEgg){
		$this->easterEggs[$easterEgg->getName()] = $easterEgg;
	}

	public function removeEasterEgg(EasterEgg $easterEgg){
		unset($this->easterEggs[$easterEgg->getName()]);
	}

	public function getEasterEgg(string $name) : ?EasterEgg{
		return $this->easterEggs[$name] ?? null;
	}

	public function getEasterEggByPos(Position $pos) : ?EasterEgg{
		foreach($this->getEasterEggs() as $easterEgg){
			if($easterEgg->getPosition()->equals($pos)){
				return $easterEgg;
			}
		}
		return null;
	}

	/**
	 * @param PlayerInteractEvent $event
	 *
	 * @handleCancelled true
	 */
	public function handleInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();

		if(isset($this->mode[$player->getName()])){
			$name = $this->mode[$player->getName()]["name"];
			$easterEgg = new EasterEgg($name, [], [], $event->getBlock()->getPosition()->getFloorX(), $event->getBlock()->getPosition()->getFloorY(), $event->getBlock()->getPosition()->getFloorZ(), $event->getBlock()->getPosition()->getWorld()->getFolderName(), 0);
			$this->addEasterEgg($easterEgg);

			OnixUtils::message($player, "이스터에그를 생성하였습니다.");
			unset($this->mode[$player->getName()]);
			return;
		}

		if(($easterEgg = $this->getEasterEggByPos($event->getBlock()->getPosition())) instanceof EasterEgg){
			if($easterEgg->canComplete($player)){
				$easterEgg->complete($player);
			}else{
				$player->sendMessage("§e§l[§fE§e] §f이미 발견한 이스터에그 입니다.");
			}
		}
	}

	public function handlePlayerQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();

		if(isset($this->mode[$player->getName()])){
			unset($this->mode[$player->getName()]);
		}
	}

	/**
	 * @return EasterEgg[]
	 */
	public function getEasterEggs() : array{
		return array_values($this->easterEggs);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($args[0] ?? "x"){
			case "생성":
			case "create":
				if(trim($args[1] ?? "") !== ""){
					if(!$this->getEasterEgg($args[1]) instanceof EasterEgg){
						$this->mode[$sender->getName()] = ["name" => $args[1]];
						OnixUtils::message($sender, "이스터에그 클리어 지점을 클릭해주세요. (표지판 터치시 표지판 바뀜)");
					}else{
						OnixUtils::message($sender, "해당 이름의 이스터에그가 이미 존재합니다.");
					}
				}else{
					OnixUtils::message($sender, "/이스터에그 생성 [이름] - 이스터에그를 생성합니다.");
				}
				break;
			case "제거":
			case "remove":
				if(trim($args[1] ?? "") !== ""){
					if(($easterEgg = $this->getEasterEgg($args[1])) instanceof EasterEgg){
						$this->removeEasterEgg($easterEgg);
						OnixUtils::message($sender, "이스터에그를 제거하였습니다.");
					}else{
						OnixUtils::message($sender, "해당 이름의 이스터에그가 존재하지 않습니다.");
					}
				}else{
					OnixUtils::message($sender, "/이스터에그 제거 [이름] - 이스터에그를 제거합니다.");
				}
				break;
			case "목록":
			case "list":
				if(count($this->easterEggs) > 0){
					OnixUtils::message($sender, "이스터에그 목록: " . implode(", ", array_map(function(EasterEgg $easterEgg) : string{
							return $easterEgg->getName();
						}, $this->getEasterEggs())));
				}else{
					OnixUtils::message($sender, "이스터에그 목록이 존재하지 않습니다.");
				}
				break;
			case "보상추가":
			case "additem":
				if($sender instanceof Player){
					if(trim($args[1] ?? "") !== ""){
						if(($easterEgg = $this->getEasterEgg($args[1])) instanceof EasterEgg){
							$item = $sender->getInventory()->getItemInHand();
							if(!$item->isNull()){
								$easterEgg->addItem($item);
								OnixUtils::message($sender, "이스터에그에 보상을 추가했습니다.");
							}else{
								OnixUtils::message($sender, "아이템의 아이디는 공기가 아니어야 합니다.");
							}
						}else{
							OnixUtils::message($sender, "해당 이름의 이스터에그가 존재하지 않습니다.");
						}
					}else{
						OnixUtils::message($sender, "/이스터에그 보상추가 [이름] - 이스터에그에 내가 든 아이템을 보상으로 추가합니다.");
					}
				}
				break;
			case "돈추가":
				if(trim($args[1] ?? "") !== ""){
					if(trim($args[2] ?? "") !== ""){
						if(($easterEgg = $this->getEasterEgg($args[1])) instanceof EasterEgg){
							if(is_numeric($args[2]) && $args[2] > 0){
								$easterEgg->setMoney((int) $args[2]);
							}
						}else{
							OnixUtils::message($sender, "해당 이름의 이스터에그가 존재하지 않습니다.");
						}
					}
				}else{
					OnixUtils::message($sender, "/이스터에그 돈추가 [이름] - 이스터에그에 돈을 추가합니다.");
				}
				break;
			case "정보":
			case "info":
				if(trim($args[1] ?? "") !== ""){
					if(($easterEgg = $this->getEasterEgg($args[1])) instanceof EasterEgg){
						$sender->sendMessage("§d===== §f[ " . $easterEgg->getName() . " ] §d=====");
						OnixUtils::message($sender, "이스터에그 보상 목록: " . implode(", ", array_map(function(Item $item) : string{
								return $item->getName() . " " . $item->getCount() . "개";
							}, $easterEgg->getItems())));
						OnixUtils::message($sender, "돈: " . $easterEgg->getMoney());
						OnixUtils::message($sender, "위치: " . OnixUtils::posToStr($easterEgg->getPosition()));
					}else{
						OnixUtils::message($sender, "해당 이름의 이스터에그가 존재하지 않습니다.");
					}
				}else{
					OnixUtils::message($sender, "/이스터에그 정보 [이름] - 이스터에그의 정보를 봅니다.");
				}
				break;
			default:
				OnixUtils::message($sender, "/이스터에그 생성 [이름] - 이스터에그를 생성합니다.");
				OnixUtils::message($sender, "/이스터에그 제거 [이름] - 이스터에그를 제거합니다.");
				OnixUtils::message($sender, "/이스터에그 목록 - 이스터에그 목록을 봅니다.");
				OnixUtils::message($sender, "/이스터에그 보상추가 [이름] - 이스터에그에 내가 든 아이템을 보상응로 추가합니다.");
				OnixUtils::message($sender, "/이스터에그 정보 [이름] - 이스터에그의 정보를 봅니다.");
		}
		return true;
	}
}