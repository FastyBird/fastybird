<?php declare(strict_types = 1);

/**
 * TemplateFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           29.08.24
 */

namespace FastyBird\Library\Application\Utilities;

use DateTimeInterface;
use FastyBird\DateTimeFactory;
use IPub\DoctrineTimestampable\Providers as DoctrineTimestampableProviders;
use Nette\DI;

/**
 * Date provider for doctrine timestampable
 *
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class DateTimeProvider implements DoctrineTimestampableProviders\DateProvider
{

	public function __construct(private DI\Container $container)
	{
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function getDate(): DateTimeInterface
	{
		return $this->container->getByType(DateTimeFactory\Factory::class)->getNow();
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function getTimestamp(): int
	{
		return $this->container->getByType(DateTimeFactory\Factory::class)->getNow()->getTimestamp();
	}

}
