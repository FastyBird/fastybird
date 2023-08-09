<?php declare(strict_types = 1);

/**
 * SetDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Request;

use FastyBird\Connector\NsPanel\Entities;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel requested set device state request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetDeviceState implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStateDirective::class)]
		private readonly SetDeviceStateDirective $directive,
	)
	{
	}

	public function getDirective(): SetDeviceStateDirective
	{
		return $this->directive;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'directive' => $this->getDirective()->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->directive = $this->getDirective()->toJson();

		return $json;
	}

}
