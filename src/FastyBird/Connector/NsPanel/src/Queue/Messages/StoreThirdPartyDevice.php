<?php declare(strict_types = 1);

/**
 * StoreThirdPartyDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Messages;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Store NS Panel third-party device details message
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreThirdPartyDevice extends Device implements Message
{

	public function __construct(
		Uuid\UuidInterface $connector,
		#[ApplicationObjectMapper\Rules\UuidValue()]
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
