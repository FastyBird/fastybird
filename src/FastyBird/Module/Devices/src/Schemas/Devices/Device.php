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

namespace FastyBird\Module\Devices\Schemas\Devices;

use Exception;
use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
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
	 * @phpstan-param T $resource
	 *
	 * @phpstan-return iterable<string, string|bool|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'identifier' => $resource->getIdentifier(),
			'name' => $resource->getName(),
			'comment' => $resource->getComment(),

			'owner' => $resource->getOwnerId(),
		];
	}

	/**
	 * @phpstan-param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Devices\Constants::ROUTE_NAME_DEVICE,
				[
					Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
				],
			),
			false,
		);
	}

	/**
	 * @phpstan-param T $resource
	 *
	 * @phpstan-return iterable<string, mixed>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_PROPERTIES => [
				self::RELATIONSHIP_DATA => $resource->getProperties(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CONTROLS => [
				self::RELATIONSHIP_DATA => $resource->getControls(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CHANNELS => [
				self::RELATIONSHIP_DATA => $this->getChannels($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CONNECTOR => [
				self::RELATIONSHIP_DATA => $resource->getConnector(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_PARENTS => [
				self::RELATIONSHIP_DATA => $this->getParents($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CHILDREN => [
				self::RELATIONSHIP_DATA => $this->getChildren($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @phpstan-param T $resource
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_PROPERTIES) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_PROPERTIES,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getProperties()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CONTROLS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_CONTROLS,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getControls()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CHANNELS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNELS,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getChannels()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_PARENTS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_PARENTS,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($this->getParents($resource)),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CHILDREN) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_CHILDREN,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($this->getChildren($resource)),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CONNECTOR) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CONNECTOR,
					[
						Router\Routes::URL_ITEM_ID => $resource->getConnector()->getPlainId(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

	/**
	 * @phpstan-param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if (
			$name === self::RELATIONSHIPS_PROPERTIES
			|| $name === self::RELATIONSHIPS_CONTROLS
			|| $name === self::RELATIONSHIPS_CHANNELS
			|| $name === self::RELATIONSHIPS_CHILDREN
			|| $name === self::RELATIONSHIPS_PARENTS
			|| $name === self::RELATIONSHIPS_CONNECTOR
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_RELATIONSHIP,
					[
						Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
						Router\Routes::RELATION_ENTITY => $name,

					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($resource, $name);
	}

	/**
	 * @phpstan-return array<Entities\Channels\Channel>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	private function getChannels(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\FindChannels();
		$findQuery->forDevice($device);

		return $this->channelsRepository->findAllBy($findQuery);
	}

	/**
	 * @phpstan-return array<Entities\Devices\Device>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	private function getParents(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\FindDevices();
		$findQuery->forChild($device);

		return $this->devicesRepository->findAllBy($findQuery);
	}

	/**
	 * @phpstan-return array<Entities\Devices\Device>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	private function getChildren(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\FindDevices();
		$findQuery->forParent($device);

		return $this->devicesRepository->findAllBy($findQuery);
	}

}
