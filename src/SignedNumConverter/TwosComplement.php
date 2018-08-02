<?php

declare(strict_types = 1);

namespace Shiyan\Binary\SignedNumConverter;

/**
 * Provides two's complement method of signed number representation.
 */
class TwosComplement implements SignedNumConverterInterface {

  /**
   * {@inheritdoc}
   */
  public static function toSigned(\GMP $num, \GMP $size): \GMP {
    if ($size <= 0) {
      throw new \InvalidArgumentException('Invalid size');
    }
    if ($num < 0) {
      throw new \InvalidArgumentException('Unsigned number cannot be negative');
    }

    $values_num = 2 ** $size;

    if ($num >= $values_num) {
      throw new \InvalidArgumentException('Number must fit into given size');
    }

    return $num >= $values_num / 2 ? $num - $values_num : $num;
  }

  /**
   * {@inheritdoc}
   */
  public static function toUnsigned(\GMP $num, \GMP $size): \GMP {
    if ($size <= 0) {
      throw new \InvalidArgumentException('Invalid size');
    }

    $values_half_num = 2 ** ($size - 1);

    if ($num >= $values_half_num || $num < -$values_half_num) {
      throw new \InvalidArgumentException('Number does not fit into given size');
    }

    return $num < 0 ? $num + 2 * $values_half_num : $num;
  }

}
