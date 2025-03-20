<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant;

use pocketmine\player\Player;
use pocketmine\world\sound\AnvilUseSound;
use function array_map;
use function explode;
use function implode;
use function strtolower;
use function ucfirst;

class Utils {
	public static function sendSound(Player $player) : void {
		if (!EconomyEnchant::getInstance()->getConfig()->get('sound')) {
			return;
		}

		$position = $player->getPosition();
		$position->getWorld()->addSound($position, new AnvilUseSound());
	}

	public static function numberToRoman(int $number) : string {
		$map = [
			1000 => 'M',
			900 => 'CM',
			500 => 'D',
			400 => 'CD',
			100 => 'C',
			90 => 'XC',
			50 => 'L',
			40 => 'XL',
			10 => 'X',
			9 => 'IX',
			5 => 'V',
			4 => 'IV',
			1 => 'I',
		];

		foreach ($map as $value => $numeral) {
			if ($number >= $value) {
				return $numeral . self::numberToRoman($number - $value);
			}
		}

		return '';
	}

	public static function capitalize(string $input) : string {
		$words = explode(' ', $input);
		$capitalized = array_map(function ($word) {
			return ucfirst(strtolower($word));
		}, $words);
		return implode(' ', $capitalized);
	}
}
