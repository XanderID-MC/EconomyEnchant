<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Manager\Enchantment;

use pocketmine\item\Item;

abstract class Enchant {
	abstract public function isCompatibleWith(mixed $enchant, Item $item) : bool;
}
