<?php declare(strict_types = 1);

/**
 * RefreshAccessToken.php
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

/**
 * OpenAPI refresh access token message
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class RefreshAccessToken implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(AccessToken::class)]
		private AccessToken $result,
	)
	{
	}

	public function getResult(): AccessToken
	{
		return $this->result;
	}

	public function toArray(): array
	{
		return [
			'result' => $this->getResult()->toArray(),
		];
	}

}
