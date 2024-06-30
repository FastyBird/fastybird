<?php declare(strict_types = 1);

/**
 * BasePresenter.php
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

use FastyBird\Library\Application\Presenters as ApplicationPresenters;
use Nette\Application;

/**
 * Base application presenter
 *
 * @package        FastyBird:Application!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class BasePresenter extends ApplicationPresenters\BasePresenter
{

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
