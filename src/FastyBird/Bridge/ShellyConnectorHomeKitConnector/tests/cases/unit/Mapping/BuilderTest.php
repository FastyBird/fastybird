<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests\Cases\Unit\Mapping;

use Error;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette\DI;
use RuntimeException;
use function array_values;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class BuilderTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws RuntimeException
	 */
	public function testBuild(): void
	{
		$builder = $this->getContainer()->getByType(Mapping\Builder::class);

		$gen1devicesMapping = $builder->getGen1Mapping();

		foreach ($gen1devicesMapping->getAccessories() as $devicesMapping) {
			self::assertNotEmpty($devicesMapping->getCategories());

			$categories = [];

			foreach ($devicesMapping->getServices() as $serviceMapping) {
				$categories[$serviceMapping->getCategory()->value] = $serviceMapping->getCategory();
			}

			self::assertSame($devicesMapping->getCategories(), array_values($categories));
		}

		$gen2devicesMapping = $builder->getGen2Mapping();

		foreach ($gen2devicesMapping->getAccessories() as $devicesMapping) {
			self::assertNotEmpty($devicesMapping->getCategories());

			$categories = [];

			foreach ($devicesMapping->getServices() as $serviceMapping) {
				$categories[$serviceMapping->getCategory()->value] = $serviceMapping->getCategory();
			}

			self::assertSame($devicesMapping->getCategories(), array_values($categories));
		}
	}

}
