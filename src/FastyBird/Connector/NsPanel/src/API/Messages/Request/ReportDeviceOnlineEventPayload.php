<?php declare(strict_types = 1);

/**
 * ReportDeviceOnlineEventPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Report third-party device online state to NS Panel event payload request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class ReportDeviceOnlineEventPayload implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		private bool $online,
	)
	{
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'online' => $this->isOnline(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->online = $this->isOnline();

		return $json;
	}

}
