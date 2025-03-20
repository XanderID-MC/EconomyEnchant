<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Provider;

use InvalidArgumentException;
use MulqiGaming64\EconomyEnchant\EconomyEnchant;
use MulqiGaming64\EconomyEnchant\Provider\Types\BedrockEconomy;
use MulqiGaming64\EconomyEnchant\Provider\Types\XP;
use pocketmine\plugin\PluginManager;
use pocketmine\Server;
use function strtolower;

class ProviderManager {
	public const DEFAULT = 'XP';
	public const ECONOMYS = ['BedrockEconomy'];

	private $provider;
	private $logger;

	public function __construct(protected EconomyEnchant $plugin) {
		$this->logger = $plugin->getLogger();
		$this->init($plugin->getConfig()->get('economy', self::DEFAULT));
	}

	private function registerProvider(string $name) : void {
		$provider = match ($name) {
			'BedrockEconomy' => new BedrockEconomy(),
			'XP' => new XP(),
			default => throw new InvalidArgumentException("Invalid provider name: {$name}"),
		};

		$this->provider = $provider;
	}

	public function getProvider() : Provider {
		return $this->provider;
	}

	public function init(string $economy) : void {
		$economyConfig = strtolower($economy);
		$pluginManager = Server::getInstance()->getPluginManager();
		$provider = match ($economyConfig) {
			'bedrockeconomy' => $this->check('BedrockEconomy', $pluginManager),
			'xp' => 'XP',
			default => $this->auto($pluginManager),
		};

		$this->registerProvider($provider);
	}

	private function check(string $pluginName, PluginManager $pluginManager) : ?string {
		if ($pluginManager->getPlugin($pluginName) === null) {
			$this->logger->alert("Economy plugin '{$pluginName}' not found. Plugin disabled!");
			$pluginManager->disablePlugin($this->plugin);
			return null;
		}

		return $pluginName;
	}

	private function auto(PluginManager $pluginManager) : string {
		$this->logger->info('No economy plugin selected, auto-detecting...');
		foreach (self::ECONOMYS as $eco) {
			if ($pluginManager->getPlugin($eco) !== null) {
				return $eco;
			}
		}

		$this->logger->alert('No economy plugin found, defaulting to ' . self::DEFAULT . '.');
		return self::DEFAULT;
	}
}
