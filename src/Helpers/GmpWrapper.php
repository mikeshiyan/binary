<?php

declare(strict_types = 1);

namespace Shiyan\Binary\Helpers;

/**
 * Wraps GMP functions in static methods with strict typing.
 */
class GmpWrapper {

  /**
   * Exports a GMP number to a binary string.
   *
   * Word size and options are default.
   *
   * @param \GMP $gmp
   *   The GMP number being exported. If the number is negative, its absolute
   *   value is exported instead.
   *
   * @return string
   *   Returns a string.
   */
  public static function export(\GMP $gmp): string {
    return gmp_export($gmp);
  }

  /**
   * Imports a GMP number from a binary string.
   *
   * Word size and options are default.
   *
   * @param string $string
   *   The binary string being imported.
   *
   * @return \GMP
   *   Returns a GMP number. There is no sign taken from the data by GMP, the
   *   result will simply represent a non-negative integer.
   */
  public static function import(string $string): \GMP {
    return gmp_import($string);
  }

  /**
   * Creates a GMP number from an integer.
   *
   * @param int $int
   *   An integer.
   *
   * @return \GMP
   *   A GMP number.
   */
  public static function init(int $int): \GMP {
    return gmp_init($int);
  }

  /**
   * Creates a GMP number from a string.
   *
   * @param string $string
   *   A string.
   * @param int $base
   *   (optional) A base. By default the actual base is determined from the
   *   leading characters of the $string.
   *
   * @return \GMP
   *   A GMP number.
   */
  public static function initFromString(string $string, int $base = 0): \GMP {
    return gmp_init($string, $base);
  }

  /**
   * Converts a GMP number into a native PHP integer.
   *
   * @param \GMP $gmp
   *   The GMP number.
   *
   * @return int
   *   The integer value of $gmp.
   *
   * @throws \DomainException
   *   If the value is too big or too small to represent it as an integer on the
   *   current build of PHP.
   */
  public static function intVal(\GMP $gmp): int {
    if ($gmp > PHP_INT_MAX || $gmp < PHP_INT_MIN) {
      throw new \DomainException('Value is beyond the integer range supported in this build of PHP');
    }

    return gmp_intval($gmp);
  }

  /**
   * Converts a GMP number to string representation in base $base.
   *
   * @param \GMP $gmp
   *   The GMP number.
   * @param int $base
   *   (optional) The base of the returned number. Allowed values for the base
   *   are from 2 to 62 and -2 to -36.
   *
   * @return string
   *   The number, as a string. Negative numbers returned same as positive ones,
   *   just with a minus sign.
   */
  public static function strVal(\GMP $gmp, int $base = 10): string {
    return gmp_strval($gmp, $base);
  }

}
