<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           13.04.19
 */

namespace FastyBird\DevicesModule\Schemas\Devices;

use FastyBird\DevicesModule;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Queries;
use FastyBird\DevicesModule\Router;
use FastyBird\DevicesModule\Schemas;
use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use Throwable;
use function count;

/**
 * Device entity schema
 *
 * @template T of Entities\Devices\Device
 * @extends  JsonApiSchemas\JsonApi<T>
 *
 * @package          FastyBird:DevicesModule!
 * @subpackage       Schemas
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends JsonApiSchemas\JsonApi
{

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_CHANNELS = 'channels';

	public const RELATIONSHIPS_PROPERTIES = 'properties';

	public const RELATIONSHIPS_CONTROLS = 'controls';

	public const RELATIONSHIPS_ATTRIBUTES = 'attributes';

	public const RELATIONSHIPS_CONNECTOR = 'connector';

	public const RELATIONSHIPS_PARENTS = 'parents';

	public const RELATIONSHIPS_CHILDREN = 'children';

	public function __construct(
		protected readonly Models\Devices\DevicesRepository $devicesRepository,
		protected readonly Models\Channels\ChannelsRepository $channelsRepository,
		protected readonly Routing\IRouter $router,
	)
	{
	}

	/**
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpstan-param T $device
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$device,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'identifier' => $device->getIdentifier(),
			'name' => $device->getName(),
			'comment' => $device->getComment(),

			'owner' => $device->getOwnerId(),
		];
	}

	/**
	 * @phpstan-param T $device
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($device): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				DevicesModule\Constants::ROUTE_NAME_DEVICE,
				[
					Router\Routes::URL_ITEM_ID => $device->getPlainId(),
				],
			),
			false,
		);
	}

	/**
	 * @return iterable<string, mixed>
	 *
	 * @phpstan-param T $device
	 *
	 * @throws Throwable
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$device,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_PROPERTIES => [
				self::RELATIONSHIP_DATA => $device->getProperties(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CONTROLS => [
				self::RELATIONSHIP_DATA => $device->getControls(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_ATTRIBUTES => [
				self::RELATIONSHIP_DATA => $device->getAttributes(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CHANNELS => [
				self::RELATIONSHIP_DATA => $this->getChannels($device),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CONNECTOR => [
				self::RELATIONSHIP_DATA => $device->getConnector(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => $device->getConnector() !== null,
			],
			self::RELATIONSHIPS_PARENTS => [
				self::RELATIONSHIP_DATA => $this->getParents($device),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CHILDREN => [
				self::RELATIONSHIP_DATA => $this->getChildren($device),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @phpstan-param T $device
	 *
	 * @throws Throwable
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$device,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_PROPERTIES) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_DEVICE_PROPERTIES,
					[
						Router\Routes::URL_DEVICE_ID => $device->getPlainId(),
					],
				),
				true,
				[
					'count' => count($device->getProperties()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CONTROLS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_DEVICE_CONTROLS,
					[
						Router\Routes::URL_DEVICE_ID => $device->getPlainId(),
					],
				),
				true,
				[
					'count' => count($device->getControls()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_ATTRIBUTES) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_DEVICE_ATTRIBUTES,
					[
						Router\Routes::URL_DEVICE_ID => $device->getPlainId(),
					],
				),
				true,
				[
					'count' => count($device->getAttributes()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CHANNELS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_CHANNELS,
					[
						Router\Routes::URL_DEVICE_ID => $device->getPlainId(),
					],
				),
				true,
				[
					'count' => count($device->getChannels()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_PARENTS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_DEVICE_PARENTS,
					[
						Router\Routes::URL_DEVICE_ID => $device->getPlainId(),
					],
				),
				true,
				[
					'count' => count($this->getParents($device)),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CHILDREN) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_DEVICE_CHILDREN,
					[
						Router\Routes::URL_DEVICE_ID => $device->getPlainId(),
					],
				),
				true,
				[
					'count' => count($this->getChildren($device)),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CONNECTOR && $device->getConnector() !== null) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_CONNECTOR,
					[
						Router\Routes::URL_ITEM_ID => $device->getConnector()->getPlainId(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipRelatedLink($device, $name);
	}

	/**
	 * @phpstan-param T $device
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$device,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if (
			$name === self::RELATIONSHIPS_PROPERTIES
			|| $name === self::RELATIONSHIPS_CONTROLS
			|| $name === self::RELATIONSHIPS_ATTRIBUTES
			|| $name === self::RELATIONSHIPS_CHANNELS
			|| $name === self::RELATIONSHIPS_CHILDREN
			|| $name === self::RELATIONSHIPS_PARENTS
			|| $name === self::RELATIONSHIPS_CONNECTOR
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_DEVICE_RELATIONSHIP,
					[
						Router\Routes::URL_ITEM_ID => $device->getPlainId(),
						Router\Routes::RELATION_ENTITY => $name,

					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($device, $name);
	}

	/**
	 * @return Array<Entities\Channels\Channel>
	 *
	 * @throws Throwable
	 */
	private function getChannels(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\FindChannels();
		$findQuery->forDevice($device);

		return $this->channelsRepository->findAllBy($findQuery);
	}

	/**
	 * @return Array<Entities\Devices\Device>
	 *
	 * @throws Throwable
	 */
	private function getParents(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\FindDevices();
		$findQuery->forChild($device);

		return $this->devicesRepository->findAllBy($findQuery);
	}

	/**
	 * @return Array<Entities\Devices\Device>
	 *
	 * @throws Throwable
	 */
	private function getChildren(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\FindDevices();
		$findQuery->forParent($device);

		return $this->devicesRepository->findAllBy($findQuery);
	}

}
