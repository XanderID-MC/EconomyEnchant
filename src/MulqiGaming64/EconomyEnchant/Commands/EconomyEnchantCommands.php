<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Commands;

use MulqiGaming64\EconomyEnchant\EconomyEnchant;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;

class EconomyEnchantCommands extends Command implements PluginOwned {
	private EconomyEnchant $plugin;

	public function __construct(EconomyEnchant $plugin) {
		$this->plugin = $plugin;
		parent::__construct('eshop', 'Economy EnchantShop', '/eshop', []);
		$this->setPermission('economyenchant.cmd');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		if (!$sender instanceof Player) {
			return false;
		}

		if (!$this->testPermission($sender)) {
			return false;
		}

		$this->getOwningPlugin()->sendShop($sender);
		return true;
	}

	public function getOwningPlugin() : EconomyEnchant {
		return $this->plugin;
	}
}
