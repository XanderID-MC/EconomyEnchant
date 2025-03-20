<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Manager;

use MulqiGaming64\EconomyEnchant\EconomyEnchant;
use MulqiGaming64\EconomyEnchant\Manager\Enchantment\Enchant;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use function array_map;
use function in_array;
use function is_a;

class EnchantManager {
	private static array $enchant = [];

	public static function getAll() : array {
		return self::$enchant;
	}

	/**
	 * @phpstan-param class-string<Enchant> $compatible
	 */
	public static function register(string $nameId, string $displayName, int $price, Enchantment $enchant, mixed $compatible) : void {
		if (!is_a($compatible, Enchant::class, true)) {
			throw new \RuntimeException('Compatible class must extend ' . Enchant::class);
		}

		self::$enchant[$displayName] = [
			'name' => $nameId,
			'price' => $price,
			'enchant' => $enchant,
			'compatible' => $compatible,
		];
	}

	public static function unregister(string $nameId) : bool {
		foreach (self::$enchant as $display => $value) {
			if ($value['name'] === $nameId) {
				unset(self::$enchant[$display]);
				return true;
			}
		}

		return false;
	}

	public static function getPriceInConfig(string $nameId) : ?int {
		$allPrice = EconomyEnchant::getInstance()->getConfig()->get('enchantment');
		return $allPrice[$nameId]['price'] ?? null;
	}

	public static function getEnchantByItem(Item $item) : array {
		$result = [];
		foreach (self::$enchant as $display => $value) {
			if (self::isEnchantBlacklisted($value['name']) || self::isItemBlacklisted($item, $value['name'])) {
				continue;
			}

			$check = new $value['compatible']();
			if ($check->isCompatibleWith($value['enchant'], $item)) {
				$result[$display] = [
					'display' => $display,
					'price' => $value['price'],
					'enchant' => $value['enchant'],
				];
			}
		}

		return $result;
	}

	public static function isEnchantBlacklisted(string $nameId) : bool {
		$blacklist = array_map('strtolower', EconomyEnchant::getInstance()->getConfig()->get('blacklist'));
		return in_array($nameId, $blacklist, true);
	}

	public static function isItemBlacklisted(Item $item, string $nameId) : bool {
		$blacklist = EconomyEnchant::getInstance()->getConfig()->get('blacklist-item');
		if (isset($blacklist[$nameId])) {
			foreach ($blacklist[$nameId] as $itemIdentifier) {
				$parsedItem = StringToItemParser::getInstance()->parse($itemIdentifier);
				if ($parsedItem !== null && $item->equals($parsedItem, true, false)) {
					return true;
				}
			}
		}

		return false;
	}

	public static function enchantItem(Player $player, Enchantment $enchant, int $level) : void {
		$item = $player->getInventory()->getItemInHand();
		$item->addEnchantment(new EnchantmentInstance($enchant, $level));
		$player->getInventory()->setItemInHand($item);
	}
}
