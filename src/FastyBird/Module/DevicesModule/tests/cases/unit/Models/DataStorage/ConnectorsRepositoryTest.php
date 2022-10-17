<?php declare(strict_types = 1);

namespace FastyBird\DevicesModule\Tests\Cases\Unit\Models\DataStorage;

use FastyBird\DevicesModule\DataStorage;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Tests\Cases\Unit\DbTestCase;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use League\Flysystem;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use RuntimeException;

final class ConnectorsRepositoryTest extends DbTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Flysystem\FilesystemException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 */
	public function setUp(): void
	{
		parent::setUp();

		$writer = $this->getContainer()->getByType(DataStorage\Writer::class);
		$reader = $this->getContainer()->getByType(DataStorage\Reader::class);

		$writer->write();
		$reader->read();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\Logic
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testReadConfiguration(): void
	{
		$connectorsRepository = $this->getContainer()->getByType(Models\DataStorage\ConnectorsRepository::class);

		self::assertCount(2, $connectorsRepository);

		$connector = $connectorsRepository->findById(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		self::assertIsObject($connector);
		self::assertSame('Blank', $connector->getName());
		self::assertSame('blank', $connector->getType());
	}

}
