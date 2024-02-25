<?php declare(strict_types = 1);

/**
 * GetUserDeviceFactoryInfos.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           14.08.23
 */

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * OpenAPI get user device factory information message
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class GetUserDeviceFactoryInfos implements API\Messages\Message
{

	/**
	 * @param array<UserDeviceFactoryInfos> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceFactoryInfos::class),
		)]
		private array $result,
	)
	{
	}

	/**
	 * @return array<UserDeviceFactoryInfos>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	public function toArray(): array
	{
		return [
			'result' => array_map(
				static fn (UserDeviceFactoryInfos $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
