<?php

declare(strict_types = 1);

namespace Shiyan\Binary\tests\unit\SignedNumConverter;

use PHPUnit\Framework\TestCase;
use Shiyan\Binary\Helpers\GmpWrapper as GMP;
use Shiyan\Binary\SignedNumConverter\TwosComplement;

class TwosComplementTest extends TestCase {

  public function exceptionsProvider(): array {
    return [
      ['toSigned', 0, 0, '/Invalid size/'],
      ['toUnsigned', 0, 0, '/Invalid size/'],
      ['toSigned', -1, 1, '/cannot be negative/'],
      ['toSigned', 2, 1, '/must fit/'],
      ['toUnsigned', 1, 1, '/does not fit/'],
      ['toUnsigned', -2, 1, '/does not fit/'],
    ];
  }

  /**
   * @dataProvider exceptionsProvider
   */
  public function testExceptions(string $method, int $num, int $size, string $msg): void {
    $method = [TwosComplement::class, $method];
    $num = GMP::init($num);
    $size = GMP::init($size);

    $this->expectExceptionMessageRegExp($msg);
    $method($num, $size);
  }

  public function conversionsProvider(): array {
    $ints = [
      [1, 0, 0],
      [1, 1, -1],
      [3, 1, 1],
      [3, 3, 3],
      [3, 4, -4],
      [3, 7, -1],
      [8, 127, 127],
      [8, 128, -128],
      [8, 255, -1],
      [16, 32767, 32767],
      [16, 32768, -32768],
      [16, 65535, -1],
    ];
    $gmps = [
      [8 * GMP::init(PHP_INT_SIZE), GMP::init(PHP_INT_MAX), GMP::init(PHP_INT_MAX)],
      [8 * GMP::init(PHP_INT_SIZE), GMP::init(PHP_INT_MAX) + 1, GMP::init(PHP_INT_MIN)],
      [8 * GMP::init(PHP_INT_SIZE), 2 * GMP::init(PHP_INT_MAX) + 1, GMP::init(-1)],
    ];

    array_walk_recursive($ints, function (&$val) {
      $val = GMP::init($val);
    });

    return array_merge($ints, $gmps);
  }

  /**
   * @dataProvider conversionsProvider
   */
  public function testConversions(\GMP $size, \GMP $unsigned, \GMP $signed): void {
    $to_signed = [TwosComplement::class, 'toSigned'];
    $to_unsigned = [TwosComplement::class, 'toUnsigned'];

    $this->assertEquals($signed, $to_signed($unsigned, $size));
    $this->assertEquals($unsigned, $to_unsigned($signed, $size));
  }

}
