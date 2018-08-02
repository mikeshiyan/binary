<?php

namespace Shiyan\Binary;

use Shiyan\Binary\StringContainer\StringContainerInterface;

/**
 * Interface for binaries.
 */
interface BinaryInterface {

  /**
   * Input interpretation flags.
   */
  const REVERSE_BYTES = 1;
  const SIGNED = 2;

  /**
   * Output representation flags.
   */
  const ZERO_PAD = 4;

  /**
   * Creates an instance.
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
   * @param int $flags
   *   (optional) Default flags for the instance, if any.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If $offset or $length is a negative integer.
   */
  public static function create(StringContainerInterface $string, int $offset = 0, int $length = NULL, int $flags = NULL): BinaryInterface;

  /**
   * Returns a part of the binary as a new instance.
   *
   * @param int $offset
   *   Offset inside the current binary.
   * @param int $length
   *   (optional) Length of the part to get. By default, the length is dynamic,
   *   so the current binary is used from the $offset until the end.
   * @param string $class
   *   (optional) Class to instantiate as the part binary.
   *
   * @return \Shiyan\Binary\BinaryInterface
   *   An instance of the part binary.
   *
   * @throws \InvalidArgumentException
   *   If $offset or $length is a negative integer, or they don't fit into the
   *   current binary length, or if $class does not implement BinaryInterface.
   */
  public function getPart(int $offset, int $length = NULL, string $class = Binary::class): BinaryInterface;

  /**
   * Sets flags to be used by the instance.
   *
   * @param int $flags
   *   Bit mask of the flags to set. See BinaryInterface constants for the
   *   available flags.
   *
   * @return static
   */
  public function setFlags(int $flags): BinaryInterface;

  /**
   * Gets flags that are used by the instance.
   *
   * @return int
   *   Bit mask of the flags.
   */
  public function getFlags(): int;

  /**
   * Sets a signed number converter class.
   *
   * @param string $class
   *   Class to convert numbers from/to signed and unsigned.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If $class does not implement SignedNumConverterInterface.
   */
  public function setSignedNumConverter(string $class): BinaryInterface;

  /**
   * Gets the length of the binary string.
   *
   * If the binary was instantiated with a dynamic length, this method returns
   * the current one.
   *
   * @return int
   *   The length in bytes.
   */
  public function getLength(): int;

  /**
   * Converts the binary string into a GMP number.
   *
   * @param int $flags
   *   (optional) Flags to use with this conversion. By default, the flags of
   *   the instance are used.
   *
   * @return \GMP
   *   The GMP number corresponding to the binary string and flags.
   *
   * @throws \LogicException
   *   If the binary is empty.
   * @throws \RuntimeException
   *   If conversion fails.
   */
  public function toGmp(int $flags = NULL): \GMP;

  /**
   * Converts the binary string into an integer.
   *
   * @param int $flags
   *   (optional) Flags to use with this conversion. By default, the flags of
   *   the instance are used.
   *
   * @return int
   *   The integer corresponding to the binary string and flags.
   *
   * @throws \LogicException
   *   If the binary is empty or if the value is too big or too small to
   *   represent it as an integer on the current build of PHP.
   * @throws \RuntimeException
   *   If conversion fails.
   */
  public function toInt(int $flags = NULL): int;

  /**
   * Converts the binary string into an ASCII representation.
   *
   * @param int $flags
   *   (optional) Flags to use with this conversion. By default, the flags of
   *   the instance are used.
   *
   * @return string
   *   The ASCII string corresponding to the binary string and flags.
   *
   * @throws \InvalidArgumentException
   *   If $flags argument contains the SIGNED flag. ASCII cannot be signed,
   *   because the minus sign is an ASCII character too. The flags of the
   *   instance are not validated this way, only the provided argument.
   * @throws \RuntimeException
   *   If conversion fails.
   */
  public function toAscii(int $flags = NULL): string;

  /**
   * Converts the binary string into a hexadecimal representation.
   *
   * @param int $flags
   *   (optional) Flags to use with this conversion. By default, the flags of
   *   the instance are used.
   *
   * @return string
   *   The hexadecimal string corresponding to the binary string and flags.
   *
   * @throws \RuntimeException
   *   If conversion fails.
   */
  public function toHex(int $flags = NULL): string;

  /**
   * Converts the binary string into a decimal representation.
   *
   * @param int $flags
   *   (optional) Flags to use with this conversion. By default, the flags of
   *   the instance are used.
   *
   * @return string
   *   The decimal string corresponding to the binary string and flags.
   *
   * @throws \RuntimeException
   *   If conversion fails.
   */
  public function toDec(int $flags = NULL): string;

  /**
   * Converts the binary string into an octal representation.
   *
   * @param int $flags
   *   (optional) Flags to use with this conversion. By default, the flags of
   *   the instance are used.
   *
   * @return string
   *   The octal string corresponding to the binary string and flags.
   *
   * @throws \RuntimeException
   *   If conversion fails.
   */
  public function toOct(int $flags = NULL): string;

  /**
   * Converts the binary string into a bits representation.
   *
   * @param int $flags
   *   (optional) Flags to use with this conversion. By default, the flags of
   *   the instance are used.
   *
   * @return string
   *   The bits string corresponding to the binary string and flags.
   *
   * @throws \RuntimeException
   *   If conversion fails.
   */
  public function toBits(int $flags = NULL): string;

  /**
   * Writes a GMP number to the binary string.
   *
   * @param \GMP $gmp
   *   A GMP number.
   * @param int $flags
   *   (optional) Flags to use with this writing. By default, the flags of the
   *   instance are used. Only "input interpretation" flags are used in writing.
   *
   * @return static
   *
   * @throws \LogicException
   *   If the binary is empty or if an unsigned number is negative.
   * @throws \LengthException
   *   If the value to write exceeds the allocated space of the binary.
   * @throws \RuntimeException
   *   If writing fails due to other reasons.
   */
  public function writeGmp(\GMP $gmp, int $flags = NULL): BinaryInterface;

  /**
   * Writes an integer to the binary string.
   *
   * @param int $int
   *   An integer to write.
   * @param int $flags
   *   (optional) Flags to use with this writing. By default, the flags of the
   *   instance are used. Only "input interpretation" flags are used in writing.
   *
   * @return static
   *
   * @throws \LogicException
   *   If the binary is empty or if an unsigned number is negative.
   * @throws \LengthException
   *   If the value to write exceeds the allocated space of the binary.
   * @throws \RuntimeException
   *   If writing fails due to other reasons.
   */
  public function writeInt(int $int, int $flags = NULL): BinaryInterface;

  /**
   * Writes an ASCII string to the binary string.
   *
   * @param string $ascii
   *   An ASCII string to write.
   * @param int $flags
   *   (optional) Flags to use with this writing. By default, the flags of the
   *   instance are used. Only "input interpretation" flags are used in writing.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If $flags argument contains the SIGNED flag. ASCII cannot be signed,
   *   because the minus sign is an ASCII character too. The flags of the
   *   instance are not validated this way, only the provided argument.
   * @throws \LengthException
   *   If the value to write exceeds the allocated space of the binary.
   * @throws \RuntimeException
   *   If writing fails due to other reasons.
   */
  public function writeAscii(string $ascii, int $flags = NULL): BinaryInterface;

  /**
   * Writes a hexadecimal string to the binary string.
   *
   * @param string $hex
   *   A hexadecimal string to write.
   * @param int $flags
   *   (optional) Flags to use with this writing. By default, the flags of the
   *   instance are used. Only "input interpretation" flags are used in writing.
   *
   * @return static
   *
   * @throws \LogicException
   *   If an unsigned number is negative.
   * @throws \LengthException
   *   If the value to write exceeds the allocated space of the binary.
   * @throws \RuntimeException
   *   If writing fails due to other reasons.
   */
  public function writeHex(string $hex, int $flags = NULL): BinaryInterface;

  /**
   * Writes a decimal string to the binary string.
   *
   * @param string $dec
   *   A decimal string to write.
   * @param int $flags
   *   (optional) Flags to use with this writing. By default, the flags of the
   *   instance are used. Only "input interpretation" flags are used in writing.
   *
   * @return static
   *
   * @throws \LogicException
   *   If an unsigned number is negative.
   * @throws \LengthException
   *   If the value to write exceeds the allocated space of the binary.
   * @throws \RuntimeException
   *   If writing fails due to other reasons.
   */
  public function writeDec(string $dec, int $flags = NULL): BinaryInterface;

  /**
   * Writes an octal string to the binary string.
   *
   * @param string $oct
   *   An octal string to write.
   * @param int $flags
   *   (optional) Flags to use with this writing. By default, the flags of the
   *   instance are used. Only "input interpretation" flags are used in writing.
   *
   * @return static
   *
   * @throws \LogicException
   *   If an unsigned number is negative.
   * @throws \LengthException
   *   If the value to write exceeds the allocated space of the binary.
   * @throws \RuntimeException
   *   If writing fails due to other reasons.
   */
  public function writeOct(string $oct, int $flags = NULL): BinaryInterface;

  /**
   * Writes bits to the binary string.
   *
   * @param string $bits
   *   Bits to write.
   * @param int $flags
   *   (optional) Flags to use with this writing. By default, the flags of the
   *   instance are used. Only "input interpretation" flags are used in writing.
   *
   * @return static
   *
   * @throws \LogicException
   *   If an unsigned number is negative.
   * @throws \LengthException
   *   If the value to write exceeds the allocated space of the binary.
   * @throws \RuntimeException
   *   If writing fails due to other reasons.
   */
  public function writeBits(string $bits, int $flags = NULL): BinaryInterface;

}
