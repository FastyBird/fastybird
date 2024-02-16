<?php declare(strict_types = 1);

/**
 * GetGatewayAccessTokenData.php
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

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel acquire access token data response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class GetGatewayAccessTokenData implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $token,
	)
	{
	}

	public function getAccessToken(): string
	{
		return $this->token;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'access_token' => $this->getAccessToken(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->token = $this->getAccessToken();

		return $json;
	}

}
