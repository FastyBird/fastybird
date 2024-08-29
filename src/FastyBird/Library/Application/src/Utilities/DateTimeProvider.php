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

	public function __construct(private DateTimeFactory\Clock $clock)
	{
	}

	public function getDate(): DateTimeInterface
	{
		return $this->clock->getNow();
	}

	public function getTimestamp(): int
	{
		return $this->clock->getNow()->getTimestamp();
	}

}
