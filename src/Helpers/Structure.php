<?php

declare(strict_types = 1);

namespace Shiyan\Binary\Helpers;

use Shiyan\Binary\Binary;
use Shiyan\Binary\BinaryInterface;

/**
 * Provides a base class for binary structures.
 */
abstract class Structure extends Binary {

  /**
   * Schema of the binary structure.
   *
   * @var array[]
   *   Keys are element names. Values are arrays of up to 3 elements in order:
   *   - (int) Byte offset of an element in the structure.
   *   - (int|null) Element length in bytes (default is NULL).
   *   - (int|string) Flags or a fully qualified class name of an element
   *     (defaults are flags of the structure instance itself).
   */
  protected static $schema = [];

  /**
   * Cached instances of elements of the structure.
   *
   * @var \Shiyan\Binary\BinaryInterface[]
   */
  protected $elements = [];

  /**
   * Returns an element of the structure as a new instance.
   *
   * @param string $name
   *   A name of the element to return.
   *
   * @return \Shiyan\Binary\BinaryInterface|self
   *   An instance of the requested element.
   *
   * @throws \InvalidArgumentException
   *   If there's no element in schema for the given name.
   */
  public function get(string $name): BinaryInterface {
    if (!isset($this->elements[$name])) {
      if (!isset(static::$schema[$name])) {
        throw new \InvalidArgumentException('Undefined element: ' . $name);
      }

      $defaults = [0, NULL, $this->flags];
      [$offset, $length, $flags_or_class] = static::$schema[$name] + $defaults;

      if (is_int($flags_or_class)) {
        $this->elements[$name] = $this->getPart($offset, $length)->setFlags($flags_or_class);
      }
      else {
        $this->elements[$name] = $this->getPart($offset, $length, $flags_or_class);
      }
    }

    return $this->elements[$name];
  }

}
