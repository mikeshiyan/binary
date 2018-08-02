<?php

declare(strict_types = 1);

namespace Shiyan\Binary\StringContainer;

/**
 * Provides a wrapper for SplFileObjects.
 */
class FileObjectContents implements StringContainerInterface {

  /**
   * File object.
   *
   * @var \SplFileObject
   */
  protected $object;

  /**
   * FileObjectContents constructor.
   *
   * @param \SplFileObject $object
   *   File object to wrap.
   */
  public function __construct(\SplFileObject $object) {
    $this->object = $object;
  }

  /**
   * {@inheritdoc}
   */
  public function tell(): int {
    $pos = $this->object->ftell();

    if (is_int($pos) && $pos >= 0) {
      return $pos;
    }

    throw new \RuntimeException(get_class($this->object) . '::ftell() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function seek(int $offset, int $whence = SEEK_SET): StringContainerInterface {
    if (!in_array($whence, [SEEK_SET, SEEK_CUR, SEEK_END])) {
      throw new \InvalidArgumentException('Invalid whence value');
    }

    if ($this->object->fseek($offset, $whence) === 0) {
      return $this;
    }

    throw new \RuntimeException(get_class($this->object) . '::fseek() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): StringContainerInterface {
    // SplFileObject::rewind() doesn't return anything, but can throw a
    // RuntimeException if something goes wrong.
    $this->object->rewind();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function eof(): bool {
    // The caller must ensure that the reading of at least 1 byte was attempted
    // (it may return empty string) right before the call to this method.
    // Because the native feof() may return FALSE at actual EOF. This is OK for
    // $this->read() where this method is called right after reading.
    // @see http://php.net/manual/function.feof.php#67261
    return $this->object->eof();
  }

  /**
   * {@inheritdoc}
   */
  public function read(int $length): string {
    if ($length < 0) {
      throw new \InvalidArgumentException('Length parameter must not be negative');
    }
    // Allow zero length. It's forbidden in fread().
    if ($length === 0) {
      return '';
    }

    $string = $this->object->fread($length);

    if (is_string($string) && (strlen($string) === $length || $this->eof())) {
      return $string;
    }

    throw new \RuntimeException(get_class($this->object) . '::fread() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function getc(): string {
    $char = $this->object->fgetc();

    if (is_string($char) && strlen($char) === 1) {
      return $char;
    }

    throw new \RuntimeException(get_class($this->object) . '::fgetc() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function write(string $string, int $length = NULL): StringContainerInterface {
    if ($length < 0) {
      throw new \InvalidArgumentException('Length parameter must not be negative');
    }
    if ($string === '' || $length === 0) {
      return $this;
    }

    // The $length argument in fwrite() is not NULL by default, it just either
    // given or not. So let's calculate one.
    $length = isset($length) ? min($length, strlen($string)) : strlen($string);

    if ($this->object->fwrite($string, $length) === $length) {
      return $this;
    }

    throw new \RuntimeException(get_class($this->object) . '::fwrite() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function flush(): StringContainerInterface {
    if ($this->object->fflush() === TRUE) {
      return $this;
    }

    throw new \RuntimeException(get_class($this->object) . '::fflush() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function truncate(int $size): StringContainerInterface {
    if ($size < 0) {
      throw new \InvalidArgumentException('Negative size is not supported');
    }

    if ($this->object->ftruncate($size) === TRUE) {
      return $this;
    }

    throw new \RuntimeException(get_class($this->object) . '::ftruncate() failed');
  }

}
