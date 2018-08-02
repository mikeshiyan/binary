<?php

declare(strict_types = 1);

namespace Shiyan\Binary\tests\unit;

use Shiyan\Binary\Binary;
use Shiyan\Binary\BinaryInterface;
use Shiyan\Binary\StringContainer\StringContainerInterface;

class ClassImplementingBinaryInterface implements BinaryInterface {

  protected $binary;

  public static function create(StringContainerInterface $string, int $offset = 0, int $length = NULL, int $flags = NULL): BinaryInterface {
    /** @var \Shiyan\Binary\tests\unit\ClassExtendingBinary $binary */
    $binary = ClassExtendingBinary::create($string, $offset, $length, $flags);
    return new static($binary);
  }

  public function __construct(Binary $binary) {
    $this->binary = $binary;
  }

  public function getPart(int $offset, int $length = NULL, string $class = ClassImplementingBinaryInterface::class): BinaryInterface {
    return $this->binary->getPart($offset, $length, $class);
  }

  public function setFlags(int $flags): BinaryInterface {
    $this->binary->setFlags($flags);
    return $this;
  }

  public function getFlags(): int {
    return $this->binary->getFlags();
  }

  public function setSignedNumConverter(string $class): BinaryInterface {
    $this->binary->setSignedNumConverter($class);
    return $this;
  }

  public function getLength(): int {
    return $this->binary->getLength();
  }

  public function toGmp(int $flags = NULL): \GMP {
    return $this->binary->toGmp($flags);
  }

  public function toInt(int $flags = NULL): int {
    return $this->binary->toInt($flags);
  }

  public function toAscii(int $flags = NULL): string {
    return $this->binary->toAscii($flags);
  }

  public function toHex(int $flags = NULL): string {
    return $this->binary->toHex($flags);
  }

  public function toDec(int $flags = NULL): string {
    return $this->binary->toDec($flags);
  }

  public function toOct(int $flags = NULL): string {
    return $this->binary->toOct($flags);
  }

  public function toBits(int $flags = NULL): string {
    return $this->binary->toBits($flags);
  }

  public function writeGmp(\GMP $gmp, int $flags = NULL): BinaryInterface {
    $this->binary->writeGmp($gmp, $flags);
    return $this;
  }

  public function writeInt(int $int, int $flags = NULL): BinaryInterface {
    $this->binary->writeInt($int, $flags);
    return $this;
  }

  public function writeAscii(string $ascii, int $flags = NULL): BinaryInterface {
    $this->binary->writeAscii($ascii, $flags);
    return $this;
  }

  public function writeHex(string $hex, int $flags = NULL): BinaryInterface {
    $this->binary->writeHex($hex, $flags);
    return $this;
  }

  public function writeDec(string $dec, int $flags = NULL): BinaryInterface {
    $this->binary->writeDec($dec, $flags);
    return $this;
  }

  public function writeOct(string $oct, int $flags = NULL): BinaryInterface {
    $this->binary->writeOct($oct, $flags);
    return $this;
  }

  public function writeBits(string $bits, int $flags = NULL): BinaryInterface {
    $this->binary->writeBits($bits, $flags);
    return $this;
  }

}
