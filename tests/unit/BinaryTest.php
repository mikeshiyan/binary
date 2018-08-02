<?php

declare(strict_types = 1);

namespace Shiyan\Binary\tests\unit;

use PHPUnit\Framework\TestCase;
use Shiyan\Binary\Binary;
use Shiyan\Binary\Helpers\GmpWrapper as GMP;
use Shiyan\Binary\StringContainer\SeekableString;
use Shiyan\Binary\tests\unit\SignedNumConverter\ClassImplementingSignedNumConverterInterface;

class BinaryTest extends TestCase {

  public function constructThrowsExceptionsProvider(): array {
    return [
      [-1, NULL],
      [0, -1],
    ];
  }

  /**
   * @dataProvider constructThrowsExceptionsProvider
   */
  public function testConstructThrowsExceptions(int $offset, ?int $length): void {
    $string = new SeekableString('string');
    $this->expectException(\InvalidArgumentException::class);
    new Binary($string, $offset, $length);
  }

  public function testGetFlags(): void {
    $string = new SeekableString('A');
    $binary = new Binary($string);

    $this->assertSame(0, $binary->getFlags());

    $random = rand(2, 1000000);
    $binary->setFlags($random);

    // Flags passed as an argument should not be preserved.
    $binary->toGmp(1);

    $this->assertSame($random, $binary->getFlags());
  }

  public function testSetSignedNumConverterThrowsExceptions(): void {
    $string = new SeekableString('A');
    $binary = new Binary($string);

    $this->expectExceptionMessageRegExp('/must implement/');
    $binary->setSignedNumConverter('');
  }

  public function getLengthProvider(): array {
    return [
      ['', 0, NULL, 0],
      [str_repeat("\0", 1000), 0, NULL, 1000],
      ['string', 0, NULL, 6],
      ['string', 2, NULL, 4],
      ['string', 0, 2, 2],
      ['string', 2, 3, 3],
      ['string', 2, 0, 0],
      ['string', 2, 10, 10],
      ['string', 6, NULL, 0],
      ['string', 10, NULL, 0],
      ['string', 10, 5, 5],
    ];
  }

  /**
   * @dataProvider getLengthProvider
   */
  public function testGetLength(string $input, int $offset, ?int $length, int $expected_length): void {
    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $this->assertSame($expected_length, $binary->getLength());
  }

  public function testGetLengthWithDynamicString(): void {
    $string = new SeekableString('string');
    $binary = new Binary($string);

    $this->assertSame(6, $binary->getLength());

    // Change the string externally. The length should change accordingly.
    $string->truncate(0)->seek(0)->write('abc');
    $this->assertSame(3, $binary->getLength());
  }

  public function testToGmpEmptyStringThrowsException(): void {
    $string = new SeekableString('');
    $binary = new Binary($string);

    $this->expectException(\LogicException::class);
    $binary->toGmp();
  }

  public function testToIntEmptyStringThrowsException(): void {
    $string = new SeekableString('');
    $binary = new Binary($string);

    $this->expectException(\LogicException::class);
    $binary->toInt();
  }

  public function testToIntLargeNumThrowsException(): void {
    // Calculate super large number for the current machine, in binary
    // representation.
    $string = str_repeat("\xff", strlen((string) PHP_INT_MAX));

    $string = new SeekableString($string);
    $binary = new Binary($string);

    $this->expectExceptionMessageRegExp('/beyond the integer range/');
    $binary->toInt();
  }

  public function testToIntSmallNumThrowsException(): void {
    // Calculate super small number for the current machine, in binary
    // representation, using the default way of the signed integer conversion.
    $string = str_repeat("\x80", strlen((string) PHP_INT_MIN));

    $string = new SeekableString($string);
    $binary = new Binary($string);

    $this->expectExceptionMessageRegExp('/beyond the integer range/');
    $binary->toInt(Binary::SIGNED);
  }

  public function toNumProvider(): array {
    return [
      ['', 0, '', '', '', '', ''],
      ["\0", 0, "\0", '0', '0', '0', '0'],
      ["\0\0", 0, "\0", '0', '0', '0', '0'],
      ["\0\0", Binary::ZERO_PAD, "\0\0", '0000', '00000', '000000', '0000000000000000'],
      ['A', 0, 'A', '41', '65', '101', '1000001'],
      ['A', Binary::ZERO_PAD, 'A', '41', '065', '101', '01000001'],
      ["\x7f", Binary::SIGNED, "\x7f", '7f', '127', '177', '1111111'],
      ["\x80", 0, "\x80", '80', '128', '200', '10000000'],
      ["\x80", Binary::SIGNED, "\x80", '-80', '-128', '-200', '-10000000'],
      ["\xff", 0, "\xff", 'ff', '255', '377', '11111111'],
      ["\xff", Binary::SIGNED, "\xff", '-1', '-1', '-1', '-1'],
      ["\xff", Binary::SIGNED | Binary::ZERO_PAD, "\xff", '-01', '-001', '-001', '-00000001'],
      ["\1\0", 0, "\1\0", '100', '256', '400', '100000000'],
      ["\0\1", Binary::REVERSE_BYTES, "\1\0", '100', '256', '400', '100000000'],
      ["\0\xff", 0, "\xff", 'ff', '255', '377', '11111111'],
      ["\xff\0", Binary::REVERSE_BYTES, "\xff", 'ff', '255', '377', '11111111'],
      ["\xff\0", Binary::REVERSE_BYTES | Binary::ZERO_PAD, "\0\xff", '00ff', '00255', '000377', '0000000011111111'],
      ["\xff\0", 0, "\xff\0", 'ff00', '65280', '177400', '1111111100000000'],
      ["\0\xff", Binary::REVERSE_BYTES, "\xff\0", 'ff00', '65280', '177400', '1111111100000000'],
      ["\xff\xff", 0, "\xff\xff", 'ffff', '65535', '177777', '1111111111111111'],
      ["\xff\xff", Binary::SIGNED | Binary::ZERO_PAD, "\xff\xff", '-0001', '-00001', '-000001', '-0000000000000001'],
    ];
  }

  /**
   * @dataProvider toNumProvider
   */
  public function testToNum(string $input, int $flags, string $ascii, string $hex, string $dec, string $oct, string $bits): void {
    $string = new SeekableString($input);
    $binary = new Binary($string);

    $this->assertSame($hex, $binary->toHex($flags));
    $this->assertSame($dec, $binary->toDec($flags));
    $this->assertSame($oct, $binary->toOct($flags));
    $this->assertSame($bits, $binary->toBits($flags));

    $binary->setFlags($flags);

    $this->assertSame($ascii, $binary->toAscii());
    $this->assertSame($hex, $binary->toHex());
    $this->assertSame($dec, $binary->toDec());
    $this->assertSame($oct, $binary->toOct());
    $this->assertSame($bits, $binary->toBits());

    $binary->setFlags(~$flags);

    $this->assertSame($hex, $binary->toHex($flags));
    $this->assertSame($dec, $binary->toDec($flags));
    $this->assertSame($oct, $binary->toOct($flags));
    $this->assertSame($bits, $binary->toBits($flags));

    // Explicitly test for no zero/nul padding of resulting strings unless
    // there is a flag stating to pad the result.
    if (~$flags & Binary::ZERO_PAD && $input !== '') {
      $binary->setFlags($flags);

      if (trim($input, "\0") === '') {
        // The input string consists of nuls only.
        $this->assertEquals(GMP::init(0), $binary->toGmp());
        $this->assertSame(0, $binary->toInt());
        $this->assertSame("\0", $binary->toAscii());
        $this->assertSame('0', $binary->toHex());
        $this->assertSame('0', $binary->toDec());
        $this->assertSame('0', $binary->toOct());
        $this->assertSame('0', $binary->toBits());
      }
      else {
        $this->assertEquals(GMP::init((int) $binary->toDec()), $binary->toGmp());
        $this->assertSame((int) $binary->toDec(), $binary->toInt());
        $this->assertStringStartsNotWith("\0", $binary->toAscii());
        $this->assertStringStartsNotWith('0', $binary->toHex());
        $this->assertStringStartsNotWith('0', $binary->toDec());
        $this->assertStringStartsNotWith('0', $binary->toOct());
        $this->assertStringStartsNotWith('0', $binary->toBits());
      }
    }
  }

  public function toAsciiWithOffsetAndLengthProvider(): array {
    return [
      ['string', 0, NULL, 0, 'string'],
      ['string', 0, 0, 0, ''],
      ['string', 2, NULL, 0, 'ring'],
      ['string', 2, 3, 0, 'rin'],
      ['string', 2, NULL, Binary::REVERSE_BYTES, 'gnir'],
      ['string', 2, 3, Binary::REVERSE_BYTES, 'nir'],
      ['string', 6, NULL, 0, ''],
      ['string', 10, NULL, 0, ''],
      ['string', 10, NULL, Binary::ZERO_PAD, ''],
      ['string', 5, 3, 0, "g\0\0"],
      ['string', 5, 3, Binary::REVERSE_BYTES | Binary::ZERO_PAD, "\0\0g"],
      ['string', 5, 3, Binary::REVERSE_BYTES, 'g'],
      ['string', 6, 2, Binary::ZERO_PAD, "\0\0"],
      ['string', 10, 10, 0, "\0"],
    ];
  }

  /**
   * @dataProvider toAsciiWithOffsetAndLengthProvider
   */
  public function testToAsciiWithOffsetAndLength(string $input, int $offset, ?int $length, int $flags, string $ascii): void {
    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $this->assertSame($ascii, $binary->toAscii($flags));

    if (!is_null($length)) {
      $this->assertSame($length, strlen($binary->toAscii(Binary::ZERO_PAD)));
    }
  }

  public function testGetPart(): void {
    $string = new SeekableString('string');
    $binary = new Binary($string, 0, NULL);

    $binary->setFlags(0b1000000);
    $sub_binary = $binary->getPart(0, NULL);

    $this->assertEquals($binary, $sub_binary);
    $this->assertNotSame($binary, $sub_binary);
    $this->assertSame($binary->getFlags(), $sub_binary->getFlags());

    $sub_binary = $binary->getPart(0, NULL, ClassExtendingBinary::class);

    $this->assertSame(ClassExtendingBinary::class, get_class($sub_binary));
    $this->assertSame(0b1000000 | Binary::SIGNED, $sub_binary->getFlags());

    $binary_wrapper = $binary->getPart(0, NULL, ClassImplementingBinaryInterface::class);

    $this->assertSame(ClassImplementingBinaryInterface::class, get_class($binary_wrapper));
    $this->assertSame(0b1000000 | Binary::SIGNED, $binary_wrapper->getFlags());

    $sub_binary_wrapper = $binary_wrapper->getPart(0, NULL);
    $this->assertSame(ClassImplementingBinaryInterface::class, get_class($sub_binary_wrapper));
    $this->assertEquals($binary_wrapper, $sub_binary_wrapper);
    $this->assertNotSame($binary_wrapper, $sub_binary_wrapper);
  }

  public function getPartThrowsExceptionsProvider(): array {
    return [
      ['string', 0, NULL, -1, NULL],
      ['string', 0, NULL, 0, -1],
      ['string', 0, 0, 0, 1],
      ['string', 0, 0, 1, NULL],
      ['string', 0, 0, 1, 0],
      ['string', 0, 0, 1, 1],
      ['string', 0, 4, 0, 5],
      ['string', 0, 4, 2, 3],
      ['string', 0, 4, 4, 1],
      ['string', 0, 4, 5, NULL],
      ['string', 0, 4, 5, 1],
      ['string', 3, 0, 0, 1],
      ['string', 3, 0, 1, NULL],
      ['string', 3, 0, 1, 0],
      ['string', 3, 0, 1, 1],
      ['string', 3, 2, 0, 3],
      ['string', 3, 2, 1, 2],
      ['string', 3, 2, 2, 1],
      ['string', 3, 2, 3, NULL],
      ['string', 3, 2, 3, 1],
    ];
  }

  /**
   * @dataProvider getPartThrowsExceptionsProvider
   */
  public function testGetPartThrowsExceptions(string $input, int $offset, ?int $length, int $sub_offset, ?int $sub_length): void {
    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $this->expectException(\InvalidArgumentException::class);
    $binary->getPart($sub_offset, $sub_length);
  }

  public function getPartAndToAsciiProvider(): array {
    return [
      ['string', 0, NULL, 0, NULL, 'string'],
      ['string', 0, NULL, 0, 0, ''],
      ['string', 0, NULL, 0, 3, 'str'],
      ['string', 0, NULL, 0, 7, "string\0"],
      ['string', 0, NULL, 1, NULL, 'tring'],
      ['string', 0, NULL, 1, 3, 'tri'],
      ['string', 0, NULL, 1, 7, "tring\0\0"],
      ['string', 0, NULL, 6, NULL, ''],
      ['string', 0, NULL, 6, 3, "\0\0\0"],
      ['string', 0, NULL, 7, NULL, ''],
      ['string', 0, NULL, 7, 3, "\0\0\0"],
      ['string', 0, 0, 0, NULL, ''],
      ['string', 0, 0, 0, 0, ''],
      ['string', 0, 5, 1, NULL, 'trin'],
      ['string', 0, 5, 1, 3, 'tri'],
      ['string', 0, 5, 5, NULL, ''],
      ['string', 0, 7, 5, NULL, "g\0"],
      ['string', 1, 4, 2, 1, 'i'],
      ['', 100, 10, 5, 2, "\0\0"],
    ];
  }

  /**
   * @dataProvider getPartAndToAsciiProvider
   */
  public function testGetPartAndToAscii(string $input, int $offset, ?int $length, int $sub_offset, ?int $sub_length, string $sub_ascii): void {
    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);
    $sub_binary = $binary->getPart($sub_offset, $sub_length);

    $this->assertSame($sub_ascii, $sub_binary->toAscii(Binary::ZERO_PAD));
  }

  public function writeIntProvider(): array {
    return [
      ['string', 2, 1, 0, 0, "st\0ing"],
      ['string', 2, 1, 255, 0, "st\xffing"],
      ['string', 2, 1, -1, Binary::SIGNED, "st\xffing"],
      ['string', 2, 2, 0, 0, "st\0\0ng"],
      ['string', 2, 2, 1, 0, "st\0\1ng"],
      ['string', 2, NULL, 1, 0, "st\0\0\0\1"],
      ['string', 2, NULL, 1, Binary::REVERSE_BYTES, "st\1\0\0\0"],
      ['string', 2, 2, -1, Binary::SIGNED, "st\xff\xffng"],
      ['string', 5, NULL, -1, Binary::SIGNED, "strin\xff"],
      ['string', 5, 2, 258, 0, "strin\1\2"],
      ['string', 5, NULL, -128, Binary::SIGNED, "strin\x80"],
      ['string', 4, NULL, -129, Binary::SIGNED, "stri\xff\x7f"],
      ['string', 4, NULL, 128, Binary::SIGNED, "stri\0\x80"],
    ];
  }

  /**
   * @dataProvider writeIntProvider
   */
  public function testWriteGmp(string $input, int $offset, ?int $length, int $int, ?int $flags, string $expected): void {
    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $binary->writeGmp(GMP::init($int), $flags);

    $this->assertSame($expected, $string->rewind()->read(100));
  }

  /**
   * @dataProvider writeIntProvider
   */
  public function testWriteInt(string $input, int $offset, ?int $length, int $int, ?int $flags, string $expected): void {
    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $binary->writeInt($int, $flags);

    $this->assertSame($expected, $string->rewind()->read(100));
  }

  public function writeIntThrowsExceptionsProvider(): array {
    return [
      ['', 0, NULL, 0, 0, '/an empty string/'],
      ['', 0, 1, -1, 0, '/cannot be negative/'],
      // Signed 128 and -129 are both 2 bytes long.
      ['A', 0, NULL, 128, Binary::SIGNED, '/does not fit/'],
      ['A', 0, NULL, -129, Binary::SIGNED, '/does not fit/'],
      ['', 0, 1, 256, 0, '/exceeds the allocated space/'],
    ];
  }

  /**
   * @dataProvider writeIntThrowsExceptionsProvider
   */
  public function testWriteIntThrowsExceptions(string $input, int $offset, ?int $length, int $int, ?int $flags, string $msg): void {
    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $this->expectExceptionMessageRegExp($msg);
    $binary->writeInt($int, $flags);
  }

  public function setSignedNumConverterAndWriteGmpThrowsExceptionsProvider(): array {
    return [
      ['A', -1, '/cannot be negative/'],
      ['A', 256, '/exceeds the allocated space/'],
    ];
  }

  /**
   * @dataProvider setSignedNumConverterAndWriteGmpThrowsExceptionsProvider
   */
  public function testSetSignedNumConverterAndWriteGmpThrowsExceptions(string $input, int $num, string $msg): void {
    $string = new SeekableString($input);
    $binary = new Binary($string);
    $binary->setSignedNumConverter(ClassImplementingSignedNumConverterInterface::class);

    $this->expectExceptionMessageRegExp($msg);
    $binary->writeGmp(GMP::init($num), Binary::SIGNED);
  }

  public function testSetSignedNumConverterAndWriteInt(): void {
    $string = new SeekableString('A');
    $binary = new Binary($string);
    $binary->setSignedNumConverter(ClassImplementingSignedNumConverterInterface::class);

    $binary->writeInt(0xff, Binary::SIGNED);
    $this->assertSame("\xff", $string->rewind()->read(2));
  }

  public function writeAsciiProvider(): array {
    return [
      ['string', 2, 0, '', 0, "string"],
      ['string', 2, 1, '', 0, "st\0ing"],
      ['string', 2, 1, "\0", 0, "st\0ing"],
      ['string', 2, 1, "\xff", 0, "st\xffing"],
      ['string', 2, 1, "\0\n", 0, "st\ning"],
      ['string', 2, 1, "\0\n", Binary::REVERSE_BYTES, "st\ning"],
      ['string', 2, 2, "\0", 0, "st\0\0ng"],
      ['string', 2, 2, "\1", 0, "st\0\1ng"],
      ['string', 2, 2, "\1", Binary::REVERSE_BYTES, "st\1\0ng"],
      ['string', 2, NULL, "\1", Binary::REVERSE_BYTES, "st\1\0\0\0"],
      ['string', 5, 2, "\1\2", 0, "strin\1\2"],
    ];
  }

  /**
   * @dataProvider writeAsciiProvider
   */
  public function testWriteAscii(string $input, int $offset, ?int $length, string $ascii, ?int $flags, string $expected): void {
    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $binary->writeAscii($ascii, $flags);

    $this->assertSame($expected, $string->rewind()->read(100));
  }

  public function writeNumProvider(): array {
    return [
      ['string', 2, 0, '', '', '', '', 0, "string"],
      ['string', 2, 1, '', '', '', '', 0, "st\0ing"],
      ['string', 2, 1, '-1', '-1', '-1', '-1', Binary::SIGNED, "st\xffing"],
      ['string', 2, 1, '0ff', '0255', '0377', '011111111', 0, "st\xffing"],
      ['string', 2, 1, '0FF', '0255', '0377', '011111111', Binary::REVERSE_BYTES, "st\xffing"],
      ['string', 5, 2, '102', '258', '402', '100000010', 0, "strin\1\2"],
      ['string', 4, NULL, '-081', '-0129', '-0201', '-010000001', Binary::SIGNED, "stri\xff\x7f"],
      ['string', 4, NULL, '80', '128', '200', '10000000', Binary::SIGNED, "stri\0\x80"],
    ];
  }

  /**
   * @dataProvider writeNumProvider
   */
  public function testWriteNum(string $input, int $offset, ?int $length, string $hex, string $dec, string $oct, string $bits, ?int $flags, string $expected): void {
    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $binary->writeHex($hex, $flags);
    $this->assertSame($expected, $string->rewind()->read(100));

    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $binary->writeDec($dec, $flags);
    $this->assertSame($expected, $string->rewind()->read(100));

    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $binary->writeOct($oct, $flags);
    $this->assertSame($expected, $string->rewind()->read(100));

    $string = new SeekableString($input);
    $binary = new Binary($string, $offset, $length);

    $binary->writeBits($bits, $flags);
    $this->assertSame($expected, $string->rewind()->read(100));
  }

  public function testWriteNumThrowsException(): void {
    $string = new SeekableString('');
    $binary = new Binary($string);

    $this->expectExceptionMessageRegExp('/exceeds the allocated space/');
    $binary->writeBits('0');
  }

}
