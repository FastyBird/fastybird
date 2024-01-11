<?php declare(strict_types = 1);

/**
 * NsPanelChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.03.22
 */

namespace FastyBird\Connector\NsPanel\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use function array_key_exists;
use function preg_match;
use function str_replace;

/**
 * @ORM\Entity
 */
class NsPanelChannel extends DevicesEntities\Channels\Channel
{

	public const TYPE = 'ns-panel-connector';

	public function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getCapability(): Types\Capability
	{
		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $this->getIdentifier(), $matches);

		if (!array_key_exists('type', $matches)) {
			throw new Exceptions\InvalidState('Device channel has invalid identifier');
		}

		$type = str_replace(' ', '', str_replace('_', '-', $matches['type']));

		if (!Types\Capability::isValidValue($type)) {
			throw new Exceptions\InvalidState('Device channel has invalid identifier');
		}

		return Types\Capability::get($type);
	}

}
