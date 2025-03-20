<?php

declare(strict_types=1);

namespace MulqiGaming64\EconomyEnchant\Transaction\Shop;

use MulqiGaming64\EconomyEnchant\EconomyEnchant;
use MulqiGaming64\EconomyEnchant\Manager\EnchantManager;
use MulqiGaming64\EconomyEnchant\Provider\Provider;
use MulqiGaming64\EconomyEnchant\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use function array_chunk;
use function array_map;
use function ksort;
use function str_replace;
use function str_starts_with;

class GUI {
	private array $gui;
	private int $remainingPage = 0;
	private array $pages = [];

	public function __construct() {
		$this->gui = EconomyEnchant::getInstance()->getConfig()->get('gui');
	}

	public function sendShop(Player $player, int $page = 0, ?InvMenu $menu = null) : void {
		$item = $player->getInventory()->getItemInHand();
		$this->buildPages($item);

		if (empty($this->pages)) {
			$player->sendMessage(EconomyEnchant::getMessage('err-item'));
			return;
		}

		if ($menu !== null) {
			$menu->getInventory()->setContents($this->getPageItems($this->remainingPage));
		} else {
			$menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
			$menu->setName($this->gui['buy-shop']['title']);
			$menu->getInventory()->setContents($this->getPageItems($page));
			$menu->setListener(fn (InvMenuTransaction $tx) : InvMenuTransactionResult => $this->handleTransaction($tx, $menu));
			$menu->setInventoryCloseListener(function (Player $player) : void {
				$player->sendMessage(EconomyEnchant::getMessage('exit'));
			});
			$menu->send($player);
		}
	}

	private function handleTransaction(InvMenuTransaction $tx, InvMenu $menu) : InvMenuTransactionResult {
		$player = $tx->getPlayer();
		$clicked = $tx->getItemClicked();
		$name = $clicked->getName();

		if (str_starts_with($name, '§fNext To Page: ')) {
			++$this->remainingPage;
			$this->sendShop($player, $this->remainingPage, $menu);
			return $tx->discard();
		}

		if (str_starts_with($name, '§fBack To Page: ')) {
			--$this->remainingPage;
			$this->sendShop($player, $this->remainingPage, $menu);
			return $tx->discard();
		}

		if (str_starts_with($name, '§fPage: ')) {
			return $tx->discard();
		}

		$this->processSubmission($clicked, $menu, $this->remainingPage);
		return $tx->discard();
	}

	private function processSubmission(Item $item, InvMenu $menu, int $page) : void {
		$tag = $item->getNamedTag();
		$display = $tag->getString('displayenchant');
		$price = $tag->getInt('price');
		$nextLevel = $tag->getInt('nextlevel');
		$enchant = $this->pages[$page]['enchant'][$display];

		$menu->setName($this->gui['submit']['title']);

		$buyItem = $this->createItem(
			VanillaItems::PAPER(),
			$this->gui['submit']['buy']['name'],
			$this->gui['submit']['buy']['lore'],
			$display,
			$nextLevel,
			$price
		);
		$cancelItem = $this->createItem(
			VanillaItems::ARROW(),
			$this->gui['submit']['cancel']['name'],
			$this->gui['submit']['cancel']['lore'],
			$display,
			$nextLevel,
			$price
		);

		$menu->getInventory()->setContents([28 => $buyItem, 34 => $cancelItem]);

		$menu->setListener(function (InvMenuTransaction $tx) use ($display, $price, $nextLevel, $enchant, $buyItem) : InvMenuTransactionResult {
			$player = $tx->getPlayer();
			$clicked = $tx->getItemClicked();
			if ($clicked->equals($buyItem)) {
				$provider = EconomyEnchant::getInstance()->getProvider();
				$provider->process($player, $price, $display, function (int $status) use ($player, $enchant, $display, $price, $nextLevel) : void {
					if ($status === Provider::STATUS_SUCCESS) {
						$currentItem = $player->getInventory()->getItemInHand();
						$msg = str_replace(
							['{price}', '{item}', '{enchant}'],
							[$price, $currentItem->getVanillaName(), $display . ' ' . Utils::numberToRoman($nextLevel)],
							EconomyEnchant::getMessage('success')
						);
						$player->sendMessage($msg);
						EnchantManager::enchantItem($player, $enchant, $nextLevel);
						Utils::sendSound($player);
						$player->removeCurrentWindow();
					} else {
						$player->removeCurrentWindow();

						$msg = str_replace('{need}', (string) $price, EconomyEnchant::getMessage('enough'));
						$player->sendMessage($msg);
					}
				});
			} else {
				$player->removeCurrentWindow();
				$player->sendMessage(EconomyEnchant::getMessage('exit'));
			}

			return $tx->discard();
		});
	}

	private function createItem(Item $baseItem, string $nameTemplate, array $loreTemplate, string $enchantName, int $level, int $price) : Item {
		$item = clone $baseItem;
		$customName = str_replace(['{enchant}', '{level}'], [$enchantName, Utils::numberToRoman($level)], $nameTemplate);
		$lore = array_map(function (string $line) use ($price) : string {
			return str_replace('{price}', (string) $price, $line);
		}, $loreTemplate);
		$item->setCustomName($customName);
		$item->setLore($lore);
		return $item;
	}

	private function buildPages(Item $item) : void {
		$enchantList = EnchantManager::getEnchantByItem($item);
		$chunks = array_chunk($enchantList, 26, true);
		$pages = [];

		foreach ($chunks as $index => $chunk) {
			foreach ($chunk as $encData) {
				$book = VanillaItems::ENCHANTED_BOOK();
				$currentLevel = $item->hasEnchantment($encData['enchant']) ? $item->getEnchantmentLevel($encData['enchant']) : 0;
				if ($encData['enchant']->getMaxLevel() === $currentLevel) {
					continue;
				}

				$nextLevel = $currentLevel + 1;
				$tag = CompoundTag::create();
				$tag->setTag('price', new IntTag($encData['price'] * $nextLevel));
				$tag->setTag('nextlevel', new IntTag($nextLevel));
				$tag->setTag('displayenchant', new StringTag($encData['display']));
				$book->setNamedTag($tag);

				$bookName = str_replace(['{enchant}', '{level}'], [$encData['display'], Utils::numberToRoman($nextLevel)], $this->gui['buy-shop']['name']);
				$bookLore = str_replace('{price}', (string) ($encData['price'] * $nextLevel), $this->gui['buy-shop']['lore']);
				$book->setCustomName($bookName);
				$book->setLore($bookLore);

				$pages[$index]['items'][$encData['display']] = $book;
				$pages[$index]['enchant'][$encData['display']] = $encData['enchant'];
			}
		}

		$this->pages = $pages;
	}

	private function getPageItems(int $page) : array {
		$items = [];
		if (!isset($this->pages[$page])) {
			return $items;
		}

		$itemList = $this->pages[$page]['items'];
		ksort($itemList);
		$index = 0;
		foreach ($itemList as $book) {
			$items[$index++] = $book;
		}

		if (isset($this->pages[$page - 1])) {
			$back = VanillaItems::ARROW();
			$back->setCustomName('§fBack To Page: ' . ($this->remainingPage - 1));
			$items[46] = $back;
		}

		if (isset($this->pages[$page + 1])) {
			$next = VanillaItems::ARROW();
			$next->setCustomName('§fNext To Page: ' . ($this->remainingPage + 1));
			$items[52] = $next;
		}

		$info = VanillaItems::BOOK();
		$info->setCustomName('§fPage: ' . ($this->remainingPage + 1));
		$items[49] = $info;
		return $items;
	}
}
