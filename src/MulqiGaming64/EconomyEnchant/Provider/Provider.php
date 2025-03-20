<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Provider;

use pocketmine\player\Player;

abstract class Provider {
	public const STATUS_SUCCESS = 0;
	public const STATUS_ENOUGH = 1;

	abstract public function process(Player $player, int $amount, string $enchantName, callable $callable) : void;
}
