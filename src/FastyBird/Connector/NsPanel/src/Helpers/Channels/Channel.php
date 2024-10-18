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

namespace FastyBird\Connector\NsPanel\Helpers\Channels;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use TypeError;
use ValueError;
use function array_key_exists;
use function preg_match;

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
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCapability(Documents\Channels\Channel $channel): Types\Capability
	{
		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $channel->getIdentifier(), $matches);

		if (!array_key_exists('capability', $matches)) {
			throw new Exceptions\InvalidState('Device channel has invalid identifier');
		}

		if (Types\Capability::tryFrom($matches['capability']) === null) {
			throw new Exceptions\InvalidState('Device channel has invalid identifier');
		}

		return Types\Capability::from($matches['capability']);
	}

	public function getName(Documents\Channels\Channel $channel): string|null
	{
		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $channel->getIdentifier(), $matches);

		if (!array_key_exists('name', $matches)) {
			return null;
		}

		return $matches['name'];
	}

}
