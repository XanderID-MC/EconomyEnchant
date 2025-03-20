<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant;

use JackMD\ConfigUpdater\ConfigUpdater;
use JackMD\UpdateNotifier\UpdateNotifier;
use MulqiGaming64\EconomyEnchant\Commands\EconomyEnchantCommands;
use MulqiGaming64\EconomyEnchant\Manager\EnchantManager;
use MulqiGaming64\EconomyEnchant\Manager\Enchantment\VanillaEnchant;
use MulqiGaming64\EconomyEnchant\Provider\Provider;
use MulqiGaming64\EconomyEnchant\Provider\ProviderManager;
use MulqiGaming64\EconomyEnchant\Transaction\Shop\GUI;
use MulqiGaming64\EconomyEnchant\Transaction\Shop\UI;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\EnchantingTable;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use XanderID\PocketForm\PocketForm;
use function class_exists;
use function implode;
use function str_replace;
use function strtolower;

class EconomyEnchant extends PluginBase {
	use SingletonTrait;

	private const CONFIG_VERSION = 4;

	private $providerManager;
	private $shopUI;

	public function onEnable() : void {
		self::setInstance($this);
		$this->saveDefaultConfig();

		if (!$this->checkVirion()) {
			return;
		}

		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());

		if (ConfigUpdater::checkUpdate($this, $this->getConfig(), 'config-version', self::CONFIG_VERSION)) {
			$this->reloadConfig();
		}

		if ((bool) $this->getConfig()->get('enchant-table', true)) {
			$this->registerEnchantTableEvent();
		}

		$mode = (bool) $this->getConfig()->get('mode', true);
		$this->registerVanillaEnchant($mode);

		$this->getServer()->getCommandMap()->register('EconomyEnchant', new EconomyEnchantCommands($this));
		$this->providerManager = new ProviderManager($this);
	}

	public function getProvider() : Provider {
		return $this->providerManager->getProvider();
	}

	public function checkVirion() : bool {
		$errorSuffix = ' not installed. Please install or download EconomyEnchant from Poggit CI. Plugin disabled!';

		$requiredDependencies = [
			'ConfigUpdater' => ConfigUpdater::class,
			'UpdateNotifier' => UpdateNotifier::class,
		];
		$missing = [];
		foreach ($requiredDependencies as $name => $class) {
			if (!class_exists($class)) {
				$missing[] = $name;
			}
		}

		if (!empty($missing)) {
			$this->getLogger()->warning('Virion: ' . implode(', ', $missing) . $errorSuffix);
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return false;
		}

		$formType = strtolower($this->getConfig()->get('form-type'));
		if ($formType === 'gui') {
			if (!$this->checkDependency(InvMenu::class, 'InvMenu', $errorSuffix)) {
				return false;
			}

			if (!InvMenuHandler::isRegistered()) {
				InvMenuHandler::register($this);
			}

			$this->shopUI = new GUI();
		} else {
			if (!$this->checkDependency(PocketForm::class, 'PocketForm', $errorSuffix)) {
				return false;
			}

			$this->shopUI = new UI();
		}

		return true;
	}

	private function checkDependency(string $class, string $name, string $errorSuffix) : bool {
		if (!class_exists($class)) {
			$this->getLogger()->warning($name . $errorSuffix);
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return false;
		}

		return true;
	}

	private function registerEnchantTableEvent() : void {
		$this->getServer()->getPluginManager()->registerEvent(
			PlayerInteractEvent::class,
			function (PlayerInteractEvent $event) : void {
				if ($event->isCancelled()) {
					return;
				}

				$player = $event->getPlayer();
				$block = $event->getBlock();
				if ($block instanceof EnchantingTable) {
					$event->cancel();
					$this->sendShop($player);
				}
			},
			EventPriority::LOWEST,
			$this
		);
	}

	private function registerVanillaEnchant(bool $mode = true) : void {
		/** @var string $name */
		foreach (VanillaEnchantments::getAll() as $name => $enchant) {
			$sname = strtolower($name);
			$displayname = Utils::capitalize(str_replace('_', ' ', $name));

			$price = EnchantManager::getPriceInConfig($sname);
			if ($price === null) {
				if (!$mode) {
					continue;
				}

				$price = EnchantManager::getPriceInConfig('default');
			}

			EnchantManager::register($sname, $displayname, $price, $enchant, VanillaEnchant::class);
		}
	}

	public function sendShop(Player $player) : void {
		$this->shopUI->sendShop($player);
	}

	public static function getMessage(string $type) : string {
		return self::$instance->getConfig()->get('message')[$type];
	}
}
