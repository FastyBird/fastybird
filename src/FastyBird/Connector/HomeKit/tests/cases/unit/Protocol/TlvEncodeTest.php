<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Tests\Cases\Unit\Protocol;

use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Connector\HomeKit\Types;
use function array_merge;
use function array_values;
use function ord;
use function unpack;

final class TlvEncodeTest extends BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function testEncode(): void
	{
		$tlvTool = new Protocol\Tlv();

		$data = [
			[
				Types\TlvCode::CODE_STATE => 3,
				Types\TlvCode::CODE_IDENTIFIER => 'hello',
			],
		];

		$result = $tlvTool->encode($data);

		$expectedResult = [
			0x06, // state
			0x01, // 1 byte value size
			0x03, // M3
			0x01, // identifier
			0x05, // 5 byte value size
			0x68, // ASCII 'h'
			0x65, // ASCII 'e'
			0x6c, // ASCII 'l'
			0x6c, // ASCII 'l'
			0x6f, // ASCII 'o'
		];

		$resultBytes = unpack('C*', $result);

		self::assertIsArray($resultBytes);
		self::assertSame($expectedResult, array_values($resultBytes));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function testEncodeWithMerge(): void
	{
		$tlvTool = new Protocol\Tlv();

		$certificate = [];

		for ($i = 0; $i < 300; $i++) {
			$certificate[] = ord('a');
		}

		$data = [
			[
				Types\TlvCode::CODE_STATE => 3,
				Types\TlvCode::CODE_CERTIFICATE => $certificate,
				Types\TlvCode::CODE_IDENTIFIER => 'hello',
			],
		];

		$result = $tlvTool->encode($data);

		$expectedResult = [
			0x06, // state
			0x01, // 1 byte value size
			0x03, // M3
			0x09, // certificate
			0xff, // 255 byte value size
			0x61, // ASCII 'a'
		];

		for ($i = 0; $i < 254; $i++) {
			$expectedResult[] = 0x61;
		}

		$expectedResult = array_merge($expectedResult, [
			0x09, // certificate, continuation of previous TLV
			0x2d, // 45 byte value size
			0x61, // ASCII 'a'
		]);

		for ($i = 0; $i < 44; $i++) {
			$expectedResult[] = 0x61;
		}

		$expectedResult = array_merge($expectedResult, [
			0x01, // identifier, new TLV item
			0x05, // 5 byte value size
			0x68, // ASCII 'h'
			0x65, // ASCII 'e'
			0x6c, // ASCII 'l'
			0x6c, // ASCII 'l'
			0x6f, // ASCII 'o'
		]);

		$resultBytes = unpack('C*', $result);

		self::assertIsArray($resultBytes);
		self::assertSame($expectedResult, array_values($resultBytes));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function testEncodeSeparated(): void
	{
		$tlvTool = new Protocol\Tlv();

		$data = [
			[
				Types\TlvCode::CODE_IDENTIFIER => 'hello',
				Types\TlvCode::CODE_PERMISSIONS => 0,
			],
			[
				Types\TlvCode::CODE_IDENTIFIER => 'world',
				Types\TlvCode::CODE_PERMISSIONS => 1,
			],
		];

		$result = $tlvTool->encode($data);

		$expectedResult = [
			0x01, // identifier
			0x05, // 5 byte value size
			0x68, // ASCII 'h'
			0x65, // ASCII 'e'
			0x6c, // ASCII 'l'
			0x6c, // ASCII 'l'
			0x6f, // ASCII 'o'
			0x0b, // permissions
			0x01, // 1 byte value size
			0x00, // user permission
			0xff, // separator
			0x00, // 0 bytes value size
			0x01, // identifier
			0x05, // 5 byte value size
			0x77, // ASCII 'w'
			0x6f, // ASCII 'o'
			0x72, // ASCII 'r'
			0x6c, // ASCII 'l'
			0x64, // ASCII 'd'
			0x0b, // permissions
			0x01, // 1 byte value size
			0x01, // admin permission
		];

		$resultBytes = unpack('C*', $result);

		self::assertIsArray($resultBytes);
		self::assertSame($expectedResult, array_values($resultBytes));
	}

}
