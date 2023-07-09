<?php declare(strict_types = 1);

/**
 * SetDeviceStatus.php
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
use Nette;
use stdClass;

/**
 * NS Panel requested set device status request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetDeviceStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(private readonly SetDeviceStatusDirective $directive)
	{
	}

	public function getDirective(): SetDeviceStatusDirective
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
