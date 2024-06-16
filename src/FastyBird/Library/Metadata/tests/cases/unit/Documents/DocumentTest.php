<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Documents;

use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Tests;
use Nette;
use Throwable;
use function file_get_contents;

final class DocumentTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @param class-string<Documents\Document> $class
	 * @param array<string, mixed> $fixture
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\MalformedInput
	 * @throws Exceptions\Mapping
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelProperty
	 */
	public function testCreateDocument(string $data, string $class, array $fixture): void
	{
		$factory = $this->container->getByType(Documents\DocumentFactory::class);

		$document = $factory->create($class, $data);

		self::assertTrue($document instanceof $class);
		self::assertEquals($fixture, $document->toArray());
	}

	/**
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\MalformedInput
	 * @throws Exceptions\Mapping
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelPropertyInvalid
	 */
	public function testCreateDocumentInvalid(string $data, string $class): void
	{
		$factory = $this->container->getByType(Documents\DocumentFactory::class);

		/** @var class-string<Throwable> $exception */
		$exception = Exceptions\Exception::class;

		$this->expectException($exception);

		$factory->create($class, $data);
	}

	/**
	 * @return array<string, array<string|bool|array<string, mixed>>>
	 */
	public static function channelProperty(): array
	{
		return [
			'dummy_one' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.one.json'),
				Tests\Fixtures\Dummy\DummyOneDocument::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'identifier' => 'dummy-document-one',
					'name' => null,
					'comment' => null,
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'dummy_two' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.json'),
				Tests\Fixtures\Dummy\DummyTwoDocument::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'identifier' => 'dummy-document-two',
					'name' => 'With some name',
					'comment' => null,
					'created_at' => '2020-04-01T12:00:00+00:00',
					'updated_at' => null,
				],
			],
		];
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelPropertyInvalid(): array
	{
		return [
			'missing' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.missing.json'),
				Tests\Fixtures\Dummy\DummyTwoDocument::class,
			],
			'type-mismatch' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/dummy.two.mismatch.json'),
				Tests\Fixtures\Dummy\DummyTwoDocument::class,
			],
		];
	}

}
