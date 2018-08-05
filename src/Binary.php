<?php

declare(strict_types = 1);

namespace Shiyan\Binary;

use Shiyan\Binary\Helpers\GmpWrapper as GMP;
use Shiyan\Binary\SignedNumConverter\SignedNumConverterInterface;
use Shiyan\Binary\SignedNumConverter\TwosComplement;
use Shiyan\Binary\StringContainer\StringContainerInterface;

/**
 * Represents a binary string.
 */
class Binary implements BinaryInterface {

  /**
   * String container instance.
   *
   * @var \Shiyan\Binary\StringContainer\StringContainerInterface
   */
  protected $string;

  /**
   * Offset inside the string container.
   *
   * @var int
   */
  protected $offset;

  /**
   * Length of the binary string.
   *
   * @var int|null
   */
  protected $length;

  /**
   * Flags bit mask.
   *
   * @var int
   */
  protected $flags = 0;

  /**
   * Signed number converter class.
   *
   * @var string
   */
  protected $signedNumConverter = TwosComplement::class;

  /**
   * {@inheritdoc}
   */
  public static function create(StringContainerInterface $string, int $offset = 0, int $length = NULL, int $flags = NULL): BinaryInterface {
    $binary = new static($string, $offset, $length);

    if (isset($flags)) {
      $binary->setFlags($flags);
    }

    return $binary;
  }

  /**
   * Binary constructor.
   *
   * @param \Shiyan\Binary\StringContainer\StringContainerInterface $string
   *   String container instance.
   * @param int $offset
   *   (optional) Offset indicating that the instance represents a part of the
   *   string in the container. By default, the string is used from its start.
   * @param int $length
   *   (optional) Length indicating that the instance represents a part of the
   *   string in the container. By default, the length is dynamic, so the string
   *   is used from the $offset until the end of the container, and this
   *   behavior isn't changed if the container grows or shrinks.
   *
   * @throws \InvalidArgumentException
   *   If $offset or $length is a negative integer.
   */
  public function __construct(StringContainerInterface $string, int $offset = 0, int $length = NULL) {
    if ($offset < 0 || $length < 0) {
      throw new \InvalidArgumentException('$offset and $length must not be negative');
    }

    $this->string = $string;
    $this->offset = $offset;
    $this->length = $length;
  }

  /**
   * {@inheritdoc}
   */
  public function getPart(int $offset, int $length = NULL, string $class = Binary::class): BinaryInterface {
    if ($offset < 0 || $length < 0) {
      throw new \InvalidArgumentException('$offset and $length must not be negative');
    }
    if (!is_subclass_of($class, BinaryInterface::class)) {
      throw new \InvalidArgumentException('$class must implement BinaryInterface');
    }

    if (isset($this->length)) {
      if ($offset > $this->length) {
        throw new \InvalidArgumentException('$offset is too big');
      }
      if (isset($length) && $offset + $length > $this->length) {
        throw new \InvalidArgumentException('$length is too big');
      }
    }

    if (isset($this->length) && !isset($length)) {
      $length = $this->length - $offset;
    }

    $offset += $this->offset;

    // Workaround for bug with the call_user_func() and the late static binding.
    // @link https://bugs.php.net/bug.php?id=64914
    $method = [$class, 'create'];
    return $method($this->string, $offset, $length);
  }

  /**
   * {@inheritdoc}
   */
  public function setFlags(int $flags): BinaryInterface {
    $this->flags = $flags;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFlags(): int {
    return $this->flags;
  }

  /**
   * Checks flags for a specific one.
   *
   * @param int $flag
   *   Flag to check for.
   * @param int|null $all_flags
   *   Flags to check. If NULL, instance's flags are checked.
   *
   * @return bool
   *   Boolean indicating whether the flag is set or not.
   */
  protected function isFlagged(int $flag, ?int $all_flags): bool {
    return (($all_flags ?? $this->flags) & $flag) == $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function setSignedNumConverter(string $class): BinaryInterface {
    if (!is_subclass_of($class, SignedNumConverterInterface::class)) {
      throw new \InvalidArgumentException('$class must implement SignedNumConverterInterface');
    }

    $this->signedNumConverter = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLength(): int {
    if (isset($this->length)) {
      return $this->length;
    }

    $length = $this->string->seek(0, SEEK_END)->tell();
    return max($length - $this->offset, 0);
  }

  /**
   * Gets the raw string from the container.
   *
   * @return string
   *   Binary string, padded with NUL bytes from right to the full length.
   */
  protected function getRawString(): string {
    $length = $this->getLength();
    $string = $this->string->seek($this->offset)->read($length);
    return str_pad($string, $length, "\0");
  }

  /**
   * Reverses a string if the REVERSE_BYTES flag is set.
   *
   * @param string $string
   *   The string.
   * @param int|null $flags
   *   Flags to check. If NULL, instance's flags are checked.
   *
   * @return string
   *   Either reversed or original string.
   */
  protected function applyCorrectDirection(string $string, ?int $flags): string {
    return $this->isFlagged(self::REVERSE_BYTES, $flags) ? strrev($string) : $string;
  }

  /**
   * {@inheritdoc}
   */
  public function toAscii(int $flags = NULL): string {
    if (isset($flags) && $flags & self::SIGNED) {
      throw new \InvalidArgumentException('ASCII cannot be signed');
    }

    $string = $this->applyCorrectDirection($this->getRawString(), $flags);

    if ($string !== '' && !$this->isFlagged(self::ZERO_PAD, $flags)) {
      $string = str_pad(ltrim($string, "\0"), 1, "\0");
    }

    return $string;
  }

  /**
   * {@inheritdoc}
   */
  public function writeAscii(string $ascii, int $flags = NULL): BinaryInterface {
    if (isset($flags) && $flags & self::SIGNED) {
      throw new \InvalidArgumentException('ASCII cannot be signed');
    }

    $ascii = ltrim($ascii, "\0");
    $length = $this->getLength();

    if (strlen($ascii) > $length) {
      throw new \LengthException('Value to write exceeds the allocated space');
    }

    $ascii = str_pad($ascii, $length, "\0", STR_PAD_LEFT);
    $ascii = $this->applyCorrectDirection($ascii, $flags);
    $this->string->seek($this->offset)->write($ascii, $length);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toGmp(int $flags = NULL): \GMP {
    $string = $this->getRawString();

    if ($string === '') {
      throw new \LogicException('Unable to convert an empty string to a number');
    }

    $gmp = GMP::import($this->applyCorrectDirection($string, $flags));

    if ($this->isFlagged(self::SIGNED, $flags)) {
      $method = [$this->signedNumConverter, 'toSigned'];
      $gmp = $method($gmp, GMP::init(8) * $this->getLength());
    }

    return $gmp;
  }

  /**
   * {@inheritdoc}
   */
  public function writeGmp(\GMP $gmp, int $flags = NULL): BinaryInterface {
    $length = $this->getLength();

    if (!$length) {
      throw new \LogicException('Unable to write a number to an empty string');
    }

    if ($this->isFlagged(self::SIGNED, $flags)) {
      $method = [$this->signedNumConverter, 'toUnsigned'];
      $gmp = $method($gmp, GMP::init(8) * $length);
    }

    if ($gmp < 0) {
      throw new \LogicException('Unsigned number cannot be negative');
    }

    // Remove the SIGNED flag, so writeAscii() won't throw exception.
    if (isset($flags)) {
      $flags &= ~self::SIGNED;
    }

    return $this->writeAscii(GMP::export($gmp), $flags);
  }

  /**
   * {@inheritdoc}
   */
  public function toInt(int $flags = NULL): int {
    return GMP::intVal($this->toGmp($flags));
  }

  /**
   * {@inheritdoc}
   */
  public function writeInt(int $int, int $flags = NULL): BinaryInterface {
    return $this->writeGmp(GMP::init($int), $flags);
  }

  /**
   * Converts the binary string into a numeric representation.
   *
   * @param int $base
   *   The base of the returned number.
   * @param int $flags
   *   (optional) Flags to use with this conversion. By default, the flags of
   *   the instance are used.
   *
   * @return string
   *   The numeric string corresponding to the binary string, base and flags.
   *
   * @throws \InvalidArgumentException
   *   If $base is not supported.
   * @throws \RuntimeException
   *   If conversion fails.
   */
  protected function toNum(int $base, int $flags = NULL): string {
    if (!in_array($base, [2, 8, 10, 16])) {
      throw new \InvalidArgumentException('Invalid $base value');
    }

    $length = $this->getLength();

    if (!$length) {
      return '';
    }

    $string = GMP::strVal($this->toGmp($flags), $base);

    if ($this->isFlagged(self::ZERO_PAD, $flags)) {
      if ($string[0] == '-') {
        $is_negative = TRUE;
        $string = substr($string, 1);
      }

      $mask = GMP::strVal(GMP::init(256) ** $length - 1, $base);
      $string = str_pad($string, strlen($mask), '0', STR_PAD_LEFT);

      if (!empty($is_negative)) {
        $string = '-' . $string;
      }
    }

    return $string;
  }

  /**
   * Writes a numeric string to the binary string.
   *
   * @param int $base
   *   The base of the number in $string.
   * @param string $string
   *   A numeric string to write.
   * @param int $flags
   *   (optional) Flags to use with this writing. By default, the flags of the
   *   instance are used. Only "input interpretation" flags are used in writing.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If given a negative number without the SIGNED flag or if $base is not
   *   supported.
   * @throws \LengthException
   *   If the value to write exceeds the allocated space of the binary.
   * @throws \RuntimeException
   *   If writing fails due to other reasons.
   */
  protected function writeNum(int $base, string $string, int $flags = NULL): BinaryInterface {
    if (!in_array($base, [2, 8, 10, 16])) {
      throw new \InvalidArgumentException('Invalid $base value');
    }

    if (!$this->getLength()) {
      if ($string !== '') {
        throw new \LengthException('Value to write exceeds the allocated space');
      }

      return $this;
    }

    $string = str_pad($string, 1, '0');
    return $this->writeGmp(GMP::initFromString($string, $base), $flags);
  }

  /**
   * {@inheritdoc}
   */
  public function toHex(int $flags = NULL): string {
    return $this->toNum(16, $flags);
  }

  /**
   * {@inheritdoc}
   */
  public function writeHex(string $hex, int $flags = NULL): BinaryInterface {
    return $this->writeNum(16, $hex, $flags);
  }

  /**
   * {@inheritdoc}
   */
  public function toDec(int $flags = NULL): string {
    return $this->toNum(10, $flags);
  }

  /**
   * {@inheritdoc}
   */
  public function writeDec(string $dec, int $flags = NULL): BinaryInterface {
    return $this->writeNum(10, $dec, $flags);
  }

  /**
   * {@inheritdoc}
   */
  public function toOct(int $flags = NULL): string {
    return $this->toNum(8, $flags);
  }

  /**
   * {@inheritdoc}
   */
  public function writeOct(string $oct, int $flags = NULL): BinaryInterface {
    return $this->writeNum(8, $oct, $flags);
  }

  /**
   * {@inheritdoc}
   */
  public function toBits(int $flags = NULL): string {
    return $this->toNum(2, $flags);
  }

  /**
   * {@inheritdoc}
   */
  public function writeBits(string $bits, int $flags = NULL): BinaryInterface {
    return $this->writeNum(2, $bits, $flags);
  }

}
