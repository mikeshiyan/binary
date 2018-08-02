<?php

namespace Shiyan\Binary\SignedNumConverter;

/**
 * Interface for signed number converters.
 */
interface SignedNumConverterInterface {

  /**
   * Converts an unsigned number to its signed representation.
   *
   * @param \GMP $num
   *   The initial, unsigned number.
   * @param \GMP $size
   *   Size of the number, in bits.
   *
   * @return \GMP
   *   The signed number.
   *
   * @throws \InvalidArgumentException
   *   If $size is not positive or if $num does not fit into $size.
   */
  public static function toSigned(\GMP $num, \GMP $size): \GMP;

  /**
   * Converts a signed number to its unsigned representation.
   *
   * @param \GMP $num
   *   The initial, signed number.
   * @param \GMP $size
   *   Size of the number, in bits.
   *
   * @return \GMP
   *   The unsigned number.
   *
   * @throws \InvalidArgumentException
   *   If $size is not positive or if $num does not fit into $size.
   */
  public static function toUnsigned(\GMP $num, \GMP $size): \GMP;

}
