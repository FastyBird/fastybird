<?php declare(strict_types = 1);

/**
 * InputButton.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           23.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Application\Documents as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\InputButton::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\InputButton::TYPE)]
class InputButton extends Shelly
{

	public static function getType(): string
	{
		return Entities\Channels\InputButton::TYPE;
	}

}
