<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Provider\Types;

use MulqiGaming64\EconomyEnchant\Provider\Provider;
use pocketmine\player\Player;

class XP extends Provider {
	public function process(Player $player, int $amount, string $enchantName, callable $callable) : void {
		$xp = $player->getXpManager();
		if ($xp->getXpLevel() >= $amount) {
			$xp->subtractXpLevels($amount);
			$callable(Provider::STATUS_SUCCESS);
		} else {
			$callable(Provider::STATUS_ENOUGH);
		}
	}
}
