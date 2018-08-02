<?php

declare(strict_types = 1);

namespace Shiyan\Binary\tests\unit\SignedNumConverter;

use Shiyan\Binary\SignedNumConverter\SignedNumConverterInterface;

class ClassImplementingSignedNumConverterInterface implements SignedNumConverterInterface {

  public static function toSigned(\GMP $num, \GMP $size): \GMP {
    return $num;
  }

  public static function toUnsigned(\GMP $num, \GMP $size): \GMP {
    return $num;
  }

}
