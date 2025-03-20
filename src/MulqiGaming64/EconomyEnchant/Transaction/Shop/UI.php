<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Transaction\Shop;

use MulqiGaming64\EconomyEnchant\EconomyEnchant;
use MulqiGaming64\EconomyEnchant\Manager\EnchantManager;
use MulqiGaming64\EconomyEnchant\Provider\Provider;
use MulqiGaming64\EconomyEnchant\Utils;
use pocketmine\player\Player;
use XanderID\PocketForm\custom\CustomForm;
use XanderID\PocketForm\custom\CustomFormResponse;
use XanderID\PocketForm\simple\element\Button;
use XanderID\PocketForm\simple\SimpleForm;
use XanderID\PocketForm\simple\SimpleFormResponse;
use function ksort;
use function str_replace;

class UI {
	private array $form;

	public function __construct() {
		$this->form = EconomyEnchant::getInstance()->getConfig()->get('form');
	}

	public function sendShop(Player $player) : void {
		$item = $player->getInventory()->getItemInHand();
		$enchantList = EnchantManager::getEnchantByItem($item);
		ksort($enchantList);

		if (empty($enchantList)) {
			$player->sendMessage(EconomyEnchant::getMessage('err-item'));
			return;
		}

		$form = new SimpleForm($this->form['buy-shop']['title']);
		$form->setBody($this->form['buy-shop']['content']);

		foreach ($enchantList as $display => $enchantData) {
			$price = $enchantData['price'];
			$buttonLabel = $this->buildButton(0, [$display, $price]) . "\n" . $this->buildButton(1, [$display, $price]);

			$button = new Button($buttonLabel);
			$button->setCustomId($display);
			$form->addElement($button);
		}

		$form->onResponse(function (SimpleFormResponse $response) use ($enchantList) : void {
			$player = $response->getPlayer();
			$selected = $response->getSelected();
			$enchantName = $selected->getId();
			if (!isset($enchantList[$enchantName])) {
				$player->sendMessage(EconomyEnchant::getMessage('err-enchant'));
				return;
			}

			$selectedEnchant = $enchantList[$enchantName];
			$selectedEnchant['display'] = $enchantName;
			$this->submit($player, $selectedEnchant);
		});
		$form->onClose(function (Player $player) : void {
			$player->sendMessage(EconomyEnchant::getMessage('exit'));
		});

		$player->sendForm($form);
	}

	private function submit(Player $player, array $encData) : void {
		$enchantment = $encData['enchant'];
		$display = $encData['display'];
		$item = $player->getInventory()->getItemInHand();
		$nowLevel = $item->hasEnchantment($enchantment) ? $item->getEnchantmentLevel($enchantment) : 0;
		$maxLevel = $enchantment->getMaxLevel();

		$form = new CustomForm($this->form['submit']['title']);
		if ($nowLevel < $maxLevel) {
			$content = str_replace('{price}', (string) $encData['price'], $this->form['submit']['content']);
			$form->addLabel($content);
			$form->addSlider($this->form['submit']['slider'], $nowLevel + 1, $maxLevel);
			$form->setSubmit($this->form['submit']['button']);
		} else {
			$form->addLabel("\n" . $this->form['submit']['max-content']);
			$form->setSubmit('Close');
		}

		$form->onResponse(function (CustomFormResponse $response) use ($encData, $display) : void {
			$player = $response->getPlayer();
			$values = $response->getValues();
			if (!isset($values[0])) {
				return;
			}

			$reqLevel = (int) $values[0];
			$price = (int) $encData['price'] * $reqLevel;
			$provider = EconomyEnchant::getInstance()->getProvider();
			$provider->process($player, $price, $display, function (int $status) use ($player, $encData, $display, $price, $reqLevel) : void {
				if ($status === Provider::STATUS_SUCCESS) {
					$currentItem = $player->getInventory()->getItemInHand();
					$msg = str_replace(
						['{price}', '{item}', '{enchant}'],
						[$price, $currentItem->getVanillaName(), $display . ' ' . Utils::numberToRoman($reqLevel)],
						EconomyEnchant::getMessage('success')
					);
					$player->sendMessage($msg);
					EnchantManager::enchantItem($player, $encData['enchant'], $reqLevel);
					Utils::sendSound($player);
				} else {
					$msg = str_replace('{need}', (string) $price, EconomyEnchant::getMessage('enough'));
					$player->sendMessage($msg);
				}
			});
		});
		$form->onClose(function (Player $player) : void {
			$player->sendMessage(EconomyEnchant::getMessage('exit'));
		});

		$player->sendForm($form);
	}

	private function buildButton(int $index, array $data) : string {
		$buttonTemplates = $this->form['buy-shop']['button'];
		return str_replace(['{enchant}', '{price}'], [$data[0], $data[1]], $buttonTemplates[$index]);
	}
}
