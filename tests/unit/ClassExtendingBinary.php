<?php

declare(strict_types = 1);

namespace Shiyan\Binary\tests\unit;

use Shiyan\Binary\Binary;
use Shiyan\Binary\BinaryInterface;
use Shiyan\Binary\StringContainer\StringContainerInterface;

class ClassExtendingBinary extends Binary {

  protected $flags = self::SIGNED;

  public static function create(StringContainerInterface $string, int $offset = 0, int $length = NULL, int $flags = NULL): BinaryInterface {
    $binary = new static('test', $string, $offset, $length);

    if (isset($flags)) {
      $binary->setFlags($flags | self::SIGNED);
    }

    return $binary;
  }

  public function __construct(string $test, StringContainerInterface $string, int $offset = 0, int $length = NULL) {
    parent::__construct($string, $offset, $length);
  }

}
