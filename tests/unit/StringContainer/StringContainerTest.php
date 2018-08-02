<?php

declare(strict_types = 1);

namespace Shiyan\Binary\tests\unit\StringContainer;

use PHPUnit\Framework\TestCase;
use Shiyan\Binary\StringContainer\FileObjectContents;
use Shiyan\Binary\StringContainer\SeekableString;
use Shiyan\Binary\StringContainer\StreamResourceContents;

class StringContainerTest extends TestCase {

  /**
   * @var \Shiyan\Binary\StringContainer\StringContainerInterface[]
   */
  protected $containers = [];

  protected function setUp() {
    $string = 'string';
    $file = '/tmp/binary-tests-str';
    file_put_contents($file, $string);

    $this->containers = [
      'StreamResourceContents' => new StreamResourceContents(fopen($file, 'r+b')),
      'FileObjectContents' => new FileObjectContents(new \SplFileObject($file, 'r+b')),
      'SeekableString' => new SeekableString($string),
    ];
  }

  protected function tearDown() {
    unlink('/tmp/binary-tests-str');
  }

  public function stringContainerProvider(): array {
    return [
      ['StreamResourceContents'],
      ['FileObjectContents'],
      ['SeekableString'],
    ];
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testSeek(string $class): void {
    $string = $this->containers[$class];
    $this->assertSame(0, $string->tell());
    $this->assertSame(7, $string->seek(7)->tell());

    $this->expectException(\RuntimeException::class);
    $string->seek(-10);
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTellAfterExceptionInSeek(string $class): void {
    $string = $this->containers[$class];
    $string->seek(3);

    try {
      $string->seek(-10);
    }
    catch (\RuntimeException $e) {
    }

    $this->assertSame(3, $string->tell());
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testSeekCur(string $class): void {
    $string = $this->containers[$class];
    $this->assertSame(0, $string->seek(0, SEEK_CUR)->tell());
    $this->assertSame(7, $string->seek(7, SEEK_CUR)->tell());
    $this->assertSame(1, $string->seek(-6, SEEK_CUR)->tell());

    $this->expectException(\RuntimeException::class);
    $string->seek(-2, SEEK_CUR);
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTellAfterExceptionInSeekCur(string $class): void {
    $string = $this->containers[$class];
    $string->seek(3);

    try {
      $string->seek(-10, SEEK_CUR);
    }
    catch (\RuntimeException $e) {
    }

    $this->assertSame(3, $string->tell());
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testSeekEnd(string $class): void {
    $string = $this->containers[$class];
    $this->assertSame(6, $string->seek(0, SEEK_END)->tell());
    $this->assertSame(7, $string->seek(1, SEEK_END)->tell());
    $this->assertSame(5, $string->seek(-1, SEEK_END)->tell());

    $this->expectException(\RuntimeException::class);
    $string->seek(-7, SEEK_END);
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTellAfterExceptionInSeekEnd(string $class): void {
    $string = $this->containers[$class];
    $string->seek(3);

    try {
      $string->seek(-10, SEEK_END);
    }
    catch (\RuntimeException $e) {
    }

    $this->assertSame(3, $string->tell());
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testSeekInvalidWhence(string $class): void {
    $string = $this->containers[$class];
    $this->expectException(\InvalidArgumentException::class);
    $string->seek(3, -10);
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTellAfterSeekInvalidWhence(string $class): void {
    $string = $this->containers[$class];
    $string->seek(3);

    try {
      $string->seek(5, -10);
    }
    catch (\InvalidArgumentException $e) {
    }

    $this->assertSame(3, $string->tell());
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testRewind(string $class): void {
    $string = $this->containers[$class];
    $this->assertSame(3, $string->seek(3)->tell());
    $this->assertSame(0, $string->rewind()->tell());
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testEof(string $class): void {
    $string = $this->containers[$class];
    $this->assertSame(FALSE, $string->eof());

    $string->seek(6)->read(1);
    $this->assertSame(TRUE, $string->eof());
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testRead(string $class): void {
    $string = $this->containers[$class];
    $this->assertSame('', $string->read(0));
    $this->assertSame(0, $string->tell());

    $this->assertSame('str', $string->read(3));
    $this->assertSame('in', $string->read(2));
    $this->assertSame(5, $string->tell());

    $this->assertSame('g', $string->read(2));
    $this->assertSame(6, $string->tell());

    $this->assertSame('', $string->seek(8)->read(2));
    $this->assertSame(8, $string->tell());
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testReadNegativeLength(string $class): void {
    $string = $this->containers[$class];
    $string->seek(3);

    $this->expectException(\InvalidArgumentException::class);
    $string->read(-1);
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTellAfterReadNegativeLength(string $class): void {
    $string = $this->containers[$class];
    $string->seek(3);

    try {
      $string->read(-1);
    }
    catch (\InvalidArgumentException $e) {
    }

    $this->assertSame(3, $string->tell());
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testGetc(string $class): void {
    $string = $this->containers[$class];
    $this->assertSame('s', $string->getc());
    $this->assertSame(1, $string->tell());

    $string->seek(-1, SEEK_END);
    $this->assertSame('g', $string->getc());
    $this->assertSame(6, $string->tell());

    $this->expectException(\RuntimeException::class);
    $string->getc();
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTellAfterExceptionInGetc(string $class): void {
    $string = $this->containers[$class];
    $string->seek(0, SEEK_END);

    try {
      $string->getc();
    }
    catch (\RuntimeException $e) {
    }

    $this->assertSame(6, $string->tell());
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testWrite(string $class): void {
    $string = $this->containers[$class];
    $this->assertSame(2, $string->write('qw')->flush()->tell());
    $this->assertSame('qwring', $string->rewind()->read(10));

    $this->assertSame(4, $string->seek(3)->write('o')->flush()->tell());
    $this->assertSame('qwrong', $string->rewind()->read(10));

    $this->assertSame(7, $string->seek(2)->write('er')->write('tyu')->flush()->tell());
    $this->assertSame('qwertyu', $string->rewind()->read(10));

    $this->assertSame(4, $string->seek(4)->write('efg')->seek(0)->write('abcd')->flush()->tell());
    $this->assertSame('abcdefg', $string->rewind()->read(10));

    // Write above the EOF.
    $this->assertSame(10, $string->seek(9)->write('j')->flush()->tell());
    $this->assertSame("abcdefg\0\0j", $string->rewind()->read(100));

    // Write providing a $length argument.
    $this->assertSame(8, $string->seek(7)->write('hi', 1)->flush()->tell());
    $this->assertSame("abcdefgh\0j", $string->rewind()->read(100));

    // $length argument is more than the actual $string length.
    $this->assertSame(9, $string->seek(8)->write('i', 3)->flush()->tell());
    $this->assertSame('abcdefghij', $string->rewind()->read(100));

    // $length is 0.
    $this->assertSame(0, $string->rewind()->write('z', 0)->flush()->tell());
    $this->assertSame('abcdefghij', $string->read(100));

    // Empty $string.
    $this->assertSame(5, $string->seek(5)->write('', 1)->flush()->tell());
    $this->assertSame('abcdefghij', $string->rewind()->read(100));

    $this->assertSame(5, $string->seek(5)->write('', 0)->flush()->tell());
    $this->assertSame('abcdefghij', $string->rewind()->read(100));
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testWriteNegativeLength(string $class): void {
    $string = $this->containers[$class];
    $string->seek(3);

    $this->expectException(\InvalidArgumentException::class);
    $string->write('a', -1);
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTellAndReadAfterWriteNegativeLength(string $class): void {
    $string = $this->containers[$class];
    $string->seek(3);

    try {
      $string->write('a', -1);
    }
    catch (\InvalidArgumentException $e) {
    }

    $this->assertSame(3, $string->flush()->tell());
    $this->assertSame('string', $string->rewind()->read(10));
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTruncate(string $class): void {
    $string = $this->containers[$class];
    $this->assertSame(0, $string->truncate(8)->tell());
    $this->assertSame("string\0\0", $string->read(10));

    $this->assertSame(5, $string->seek(5)->truncate(3)->tell());
    $this->assertSame('str', $string->rewind()->read(10));

    // Truncate to an empty string.
    $this->assertSame('', $string->truncate(0)->rewind()->read(10));
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTruncateNegativeSize(string $class): void {
    $string = $this->containers[$class];
    $this->expectException(\InvalidArgumentException::class);
    $string->truncate(-1);
  }

  /**
   * @dataProvider stringContainerProvider
   */
  public function testTellAndReadAfterTruncateNegativeSize(string $class): void {
    $string = $this->containers[$class];
    $string->seek(3);

    try {
      $string->truncate(-1);
    }
    catch (\InvalidArgumentException $e) {
    }

    $this->assertSame(3, $string->tell());
    $this->assertSame('string', $string->rewind()->read(10));
  }

}
