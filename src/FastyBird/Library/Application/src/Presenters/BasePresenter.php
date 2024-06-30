<?php declare(strict_types = 1);

/**
 * BasePresenter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     Presenters
 * @since          1.0.0
 *
 * @date           16.06.24
 */

namespace FastyBird\Library\Application\Presenters;

use FastyBird\Library\Application\Exceptions;
use FastyBird\Library\Application\UI;
use Nette\Application;
use function preg_match;

/**
 * Base application presenter
 *
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     Presenters
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class BasePresenter extends Application\UI\Presenter
{

	private UI\TemplateFactory|null $templateFactory = null;

	public function injectTemplateFactory(UI\TemplateFactory $templateFactory): void
	{
		$this->templateFactory = $templateFactory;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function formatLayoutTemplateFiles(): array
	{
		if (
			$this->layout !== null
			&& $this->layout !== ''
			&& preg_match('#/|\\\\#', (string) $this->layout) === 1
		) {
			return [(string) $this->layout];
		}

		if ($this->templateFactory?->getLayouts() === []) {
			throw new Exceptions\InvalidState('No layouts are specified.');
		}

		return $this->templateFactory?->getLayouts() ?? [];
	}

}
