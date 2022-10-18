<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Devices\Entities;

/**
 * @ORM\Entity
 */
class DummyConnectorEntity extends Entities\Connectors\Connector
{

	public const CONNECTOR_TYPE = 'dummy';

	public function getType(): string
	{
		return 'dummy';
	}

	public function getDiscriminatorName(): string
	{
		return 'dummy';
	}

}
