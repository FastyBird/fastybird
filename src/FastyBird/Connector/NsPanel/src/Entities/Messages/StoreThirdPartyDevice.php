<?php declare(strict_types = 1);

/**
 * StoreThirdPartyDevice.php
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
use function array_merge;

/**
 * Store NS Panel third-party device details message entity
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreThirdPartyDevice extends Device implements Entity
{

	public function __construct(
		Uuid\UuidInterface $connector,
		#[BootstrapObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $gateway,
		string $identifier,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('gateway_identifier')]
		private readonly string $gatewayIdentifier,
	)
	{
		parent::__construct($connector, $identifier);
	}

	public function getGateway(): Uuid\UuidInterface
	{
		return $this->gateway;
	}

	public function getGatewayIdentifier(): string
	{
		return $this->gatewayIdentifier;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'gateway' => $this->getGateway()->toString(),
			'gateway_identifier' => $this->getGatewayIdentifier(),
		]);
	}

}
