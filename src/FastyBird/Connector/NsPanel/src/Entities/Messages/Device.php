<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\Messages;

use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Base device message entity
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device implements Entity
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'identifier' => $this->getIdentifier(),
		];
	}

}
