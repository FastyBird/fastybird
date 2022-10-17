<?php declare(strict_types = 1);

namespace FastyBird\DevicesModule\Tests\Fixtures\Dummy;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\DevicesModule\Entities;

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
