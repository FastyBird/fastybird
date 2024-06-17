<?php declare(strict_types = 1);

/**
 * DefaultPresenter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Presenters
 * @since          1.0.0
 *
 * @date           16.06.24
 */

namespace FastyBird\App\Presenters;

use Nette\Application;
use function dirname;
use function is_bool;
use function preg_match;

/**
 * Default application presenter
 *
 * @package        FastyBird:Application!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class BasePresenter extends Application\UI\Presenter
{

	public function formatLayoutTemplateFiles(): array
	{
		if (preg_match('#/|\\\\#', (string) $this->layout) !== false) {
			return [(string) $this->layout];
		}

		[$module, $presenter] = Application\Helpers::splitName($this->getName() ?? '');

		$layout = is_bool($this->layout) ? 'layout' : $this->layout;

		$dir = __DIR__ . '/../../templates/';

		$list = [
			"$dir/presenters/$presenter/@$layout.latte",
			"$dir/presenters/$presenter.@$layout.latte",
		];

		do {
			$list[] = "$dir/@$layout.latte";

			$dir = dirname($dir);

			[$module] = Application\Helpers::splitName($module);
		} while ($dir !== '' && $module !== '');

		return $list;
	}

	public function formatTemplateFiles(): array
	{
		[, $presenter] = Application\Helpers::splitName($this->getName() ?? '');

		$dir = __DIR__ . '/../../templates/';

		return [
			"$dir/presenters/$presenter/$this->view.latte",
			"$dir/presenters/$presenter.$this->view.latte",
			"$dir/presenters/$presenter.latte",
		];
	}

}
