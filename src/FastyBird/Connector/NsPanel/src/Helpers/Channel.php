<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           01.12.23
 */

namespace FastyBird\Connector\NsPanel\Helpers;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use function array_key_exists;
use function preg_match;
use function str_replace;

/**
 * Channel helper
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Channel
{

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getCapability(MetadataDocuments\DevicesModule\Channel $channel): Types\Capability
	{
		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $channel->getIdentifier(), $matches);

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
