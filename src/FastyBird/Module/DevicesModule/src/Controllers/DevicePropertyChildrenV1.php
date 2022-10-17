<?php declare(strict_types = 1);

/**
 * DevicePropertyChildrenV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          0.33.0
 *
 * @date           09.02.22
 */

namespace FastyBird\DevicesModule\Controllers;

use Exception;
use FastyBird\DevicesModule\Controllers;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Queries;
use FastyBird\DevicesModule\Router;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use Psr\Http\Message;
use function strval;

/**
 * Device property children API controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\User(loggedIn)
 */
final class DevicePropertyChildrenV1 extends BaseV1
{

	use Controllers\Finders\TDevice;
	use Controllers\Finders\TDeviceProperty;

	public function __construct(
		protected readonly Models\Devices\DevicesRepository $devicesRepository,
		private readonly Models\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
	)
	{
	}

	/**
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load device
		$device = $this->findDevice(strval($request->getAttribute(Router\Routes::URL_DEVICE_ID)));
		// & property
		$property = $this->findProperty(strval($request->getAttribute(Router\Routes::URL_PROPERTY_ID)), $device);

		$findQuery = new Queries\FindDeviceProperties();
		$findQuery->forParent($property);

		$children = $this->devicePropertiesRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $children);
	}

}
