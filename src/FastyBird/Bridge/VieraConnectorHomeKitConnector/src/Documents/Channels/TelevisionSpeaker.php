<?php declare(strict_types = 1);

/**
 * TelevisionSpeaker.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Core\Application\Documents as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\TelevisionSpeaker::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\TelevisionSpeaker::TYPE)]
class TelevisionSpeaker extends Viera
{

	public static function getType(): string
	{
		return Entities\Channels\TelevisionSpeaker::TYPE;
	}

}
