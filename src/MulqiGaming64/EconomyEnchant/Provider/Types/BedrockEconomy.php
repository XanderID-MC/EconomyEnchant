<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Provider\Types;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\type\ClosureAPI;
use MulqiGaming64\EconomyEnchant\Provider\Provider;
use pocketmine\player\Player;

class BedrockEconomy extends Provider {
	private ClosureAPI $bedrockEconomyAPI;

	public function __construct() {
		$this->bedrockEconomyAPI = BedrockEconomyAPI::CLOSURE();
	}

	public function process(Player $player, int $amount, string $enchantName, callable $callable) : void {
		$this->bedrockEconomyAPI->subtract(
			$player->getXuid(),
			$player->getName(),
			$amount,
			0,
			fn () => $callable(Provider::STATUS_SUCCESS),
			fn () => $callable(Provider::STATUS_ENOUGH)
		);
	}
}
