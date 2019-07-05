<?php
namespace DewCrate;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\network\mcpe\protocol\AddItemEntityPacket;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\ItemEntity;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\TaskHandler;
use pocketmine\inventory\PlayerInventory;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\nbt\NBT;
use pocketmine\scheduler\Task;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\block\EnderChest;
use pocketmine\utils\Config;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\utils\TextFormat as C;

class Main extends PluginBase implements Listener{
	public $cfg;
	public $id;
	public $ftext;
	public $tasks;
	public $level;
	public $player;
	public $arrplayer = [];
	public $Use;
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        
        if(!is_dir($this->getDataFolder())){
			@mkdir($this->getDataFolder());
		}
		if(!file_exists($this->getDataFolder() . "config.yml")){
			$this->saveDefaultConfig();
		}
	$this->cfg = $this->getDataFolder() . "config.yml";
    }
    
	public function onCommand(CommandSender $sender, Command $cmd, string $label,array $args) : bool {
	if((strtolower($cmd->getName())) == "dkey"){
	if($sender instanceOf Player and $sender->isOp()){
	if(!isset($args[0])){
	$sender->sendMessage("/dkey common/rare/legendary");
	return true;
	}
$item = Item::get(131,0,1);
$enchantment = Enchantment::getEnchantment(0);
$enchantment = new EnchantmentInstance($enchantment, 0);
$item->addEnchantment($enchantment);
switch(strtolower($args[0])){
    case "common":
      $nbt = $item->getNamedTag();
      $nbt->setString("dcrate", "common");
      $item->setCustomName("Common key");
     // $sender->sendMessage("Value: ".$nbt->getString("dcrate"));  
        break;
        
    case "rare":
      $nbt = $item->getNamedTag();
      $nbt->setString("dcrate", "rare");
      $item->setCustomName("Rare Key");
      break;
      
    case "legendary":
      $nbt = $item->getNamedTag();
      $nbt->setString("dcrate", "legendary");
      $item->setCustomName("Legendary Key");
      break;

}
$sender->getPlayer()->getInventory()->addItem($item);
	}
	}
return true;	
	}
	
	public function common($prize){
	$item = $this->getConfig()->get("common");
	$rand = mt_rand(0,count($item)-1);
	$id = strval($item[$rand]);
	$n = explode(":",$id);
	if(is_numeric($n[0]) and is_numeric($n[1])) return $prize = $id;
	}
	
	public function rare($prize){
	$item = $this->getConfig()->get("rare");
	$rand = mt_rand(0,count($item)-1);
	$id = strval($item[$rand]);
	$n = explode(":",$id);
	if(is_numeric($n[0]) and is_numeric($n[1])) return $prize = $id;    
	}
	
	public function legendary($prize){
	$item = $this->getConfig()->get("legendary");
	$rand = mt_rand(0,count($item)-1);
	$id = strval($item[$rand]);
	$n = explode(":",$id);
	if(is_numeric($n[0]) and is_numeric($n[1])) return $prize = $id;    
	}
	
	public function onInteract(PlayerInteractEvent $e){
	 $block = $e->getBlock();
	 $bc = $block->getSide(Vector3::SIDE_UP);
	 $player = $e->getPlayer();
	 $this->player = $player;
	 array_push($this->arrplayer,$player);
	 $this->level = $player->getLevel();
	 $item = $player->getInventory()->getItemInHand();
	 $nbt = $item->getNamedTag();
	 if($item->getId() === 131){
	 if(!$nbt->hasTag("dcrate")) return true;
	 $value = $nbt->getString("dcrate");
	 if($block instanceOf EnderChest){
	     if($this->Use == 0){
	 $this->Use = 1;
	     $prize = 0;
	 switch($value){
	  case "common":
	      $prize =$this->common($prize);
	      $i = explode(":",$prize);
	      $player->getInventory()->addItem(Item::get($i[0],$i[1],$i[2]));
	      break;
	  case "rare":
	      $prize =$this->rare($prize);
	      $i = explode(":",$prize);
	      $player->getInventory()->addItem(Item::get($i[0],$i[1],$i[2]));
	      $this->rare($prize);
	      break;
	  case "legendary":
	      $prize =$this->legendary($prize);
	      $i = explode(":",$prize);
	      $player->getInventory()->addItem(Item::get($i[0],$i[1],$i[2]));
	      break;
	      
	      default:
	          return true;
	 }
	 $pk = new AddItemEntityPacket();
	    $this->id = Entity::$entityCount++;
		$pk->entityRuntimeId = $this->id;
		$pk->position = new Vector3($bc->getX()+0.5,$bc->getY()+1,$bc->getZ()+0.5);
		$pk->motion = new Vector3(0,0,0);
		$i = explode(":",$prize);
		$it = Item::get($i[0],$i[1],$i[2]);
		$pk->item = $it; 
		$pk->metadata = [];
		
if($player->getGamemode() != 1){
	 $item->pop();
	 $player->getInventory()->setItemInHand($item);
	 
	 }
	 
$this->ftext = new FloatingTextParticle(new Vector3($bc->getX()+0.5,$bc->getY()+0.5,$bc->getZ()+0.5),C::GREEN.C::UNDERLINE.$it->getName(),$value); 
	    $player->getLevel()->addParticle($this->ftext,$this->arrplayer);
		$player->dataPacket($pk);
	    
	 $task = new Timr($this);	
    $h = $this->getScheduler()->scheduleRepeatingTask($task,20);
	$task->setHandler($h);
    $this->tasks[$task->getTaskId()] = $task->getTaskId();
    
    $e->setCancelled();   
	 }else{
	 $e->setCancelled();
	 $player->sendMessage(C::RED.C::UNDERLINE."Can not open multiple crates at once");
	 }
	 }else{
	 $e->setCancelled();
	 }
	 
	 
	 }
	 } 
	
	
	public function stopTask($id){
	unset($this->tasks[$id]);
	$this->getScheduler()->cancelTask($id);
	
	$this->ftext->setInvisible(true);
	 $this->level->addParticle($this->ftext,$this->arrplayer);
	 unset($this->arrplayer[0]);
	 $pk = new RemoveEntityPacket();
        $pk->entityUniqueId = $this->id;
        $this->player->dataPacket($pk);
	    $this->Use = 0;
	    
	}
    
    public function onDisable(){
     $this->getLogger()->info("§cOffline");
    }
}

class Timr extends Task {
  
  public $plugin;
  public $t = 0;
    
  public function __construct(Main $plugin)
  {
    $this->plugin = $plugin;
  }
  
public function onRun($tick){
$this->t++;
if($this->t == 3){
$this->plugin->stopTask($this->getTaskId());   
}
}
}