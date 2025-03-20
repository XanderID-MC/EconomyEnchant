<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Manager\Enchantment;

use pocketmine\item\enchantment\AvailableEnchantmentRegistry;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;

class VanillaEnchant extends Enchant {
	/**
	 * @param Enchantment $enchant
	 */
	public function isCompatibleWith(mixed $enchant, Item $item) : bool {
		$registry = AvailableEnchantmentRegistry::getInstance();
		return $registry->isAvailableForItem($enchant, $item);
	}
}
