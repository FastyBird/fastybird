<?php declare(strict_types = 1);

/**
 * DeviceSynchronisation.php
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

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Device synchronisation message entity
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceSynchronisation extends Device implements Entity
{

	public function __construct(
		Uuid\UuidInterface $connector,
		string $identifier,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('gateway_identifier')]
		private readonly string $gatewayIdentifier,
	)
	{
		parent::__construct($connector, $identifier);
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
			'gateway_identifier' => $this->getGatewayIdentifier(),
		]);
	}

}
