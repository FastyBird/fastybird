<?php declare(strict_types = 1);

/**
 * Container.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Library\Exchange\Consumers;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;

/**
 * Consumer configuration
 *
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Info
{

	use Nette\SmartObject;

	public function __construct(
		private readonly MetadataTypes\RoutingKey|null $routingKey,
		private readonly bool $enabled,
	)
	{
	}

	public function getRoutingKey(): MetadataTypes\RoutingKey|null
	{
		return $this->routingKey;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

}
