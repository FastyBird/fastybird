<?php declare(strict_types = 1);

/**
 * TemplateFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     Caching
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Library\Application\UI;

use FastyBird\Library\Application\Exceptions;
use function file_exists;
use function sprintf;

class TemplateFactory
{

	/** @var array<string> */
	private array $layouts = [];

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function registerLayout(string $layout): void
	{
		if (!file_exists($layout)) {
			throw new Exceptions\InvalidArgument(sprintf('Provided layout file: "%s" does not exist', $layout));
		}

		$this->layouts[] = $layout;
	}

	/**
	 * @return array<string>
	 */
	public function getLayouts(): array
	{
		return $this->layouts;
	}

}
