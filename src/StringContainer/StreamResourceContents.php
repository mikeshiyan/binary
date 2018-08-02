<?php

declare(strict_types = 1);

namespace Shiyan\Binary\StringContainer;

/**
 * Provides a wrapper for "stream" resources.
 */
class StreamResourceContents implements StringContainerInterface {

  /**
   * Stream resource.
   *
   * @var resource
   */
  protected $stream;

  /**
   * StreamResourceContents constructor.
   *
   * @param resource $resource
   *   Resource of the "stream" type.
   *
   * @throws \InvalidArgumentException
   *   If provided argument is not a valid open stream resource.
   */
  public function __construct($resource) {
    if (!is_resource($resource) || get_resource_type($resource) != 'stream') {
      throw new \InvalidArgumentException('$resource must be a valid resource of the "stream" type');
    }

    $this->stream = $resource;
  }

  /**
   * {@inheritdoc}
   */
  public function tell(): int {
    $pos = ftell($this->stream);

    if (is_int($pos) && $pos >= 0) {
      return $pos;
    }

    throw new \RuntimeException('ftell() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function seek(int $offset, int $whence = SEEK_SET): StringContainerInterface {
    if (!in_array($whence, [SEEK_SET, SEEK_CUR, SEEK_END])) {
      throw new \InvalidArgumentException('Invalid whence value');
    }

    if (fseek($this->stream, $offset, $whence) === 0) {
      return $this;
    }

    throw new \RuntimeException('fseek() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): StringContainerInterface {
    if (rewind($this->stream) === TRUE) {
      return $this;
    }

    throw new \RuntimeException('rewind() failed');
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
    return feof($this->stream);
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

    $string = fread($this->stream, $length);

    if (is_string($string) && (strlen($string) === $length || $this->eof())) {
      return $string;
    }

    throw new \RuntimeException('fread() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function getc(): string {
    $char = fgetc($this->stream);

    if (is_string($char) && strlen($char) === 1) {
      return $char;
    }

    throw new \RuntimeException('fgetc() failed');
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

    if (fwrite($this->stream, $string, $length) === $length) {
      return $this;
    }

    throw new \RuntimeException('fwrite() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function flush(): StringContainerInterface {
    if (fflush($this->stream) === TRUE) {
      return $this;
    }

    throw new \RuntimeException('fflush() failed');
  }

  /**
   * {@inheritdoc}
   */
  public function truncate(int $size): StringContainerInterface {
    if ($size < 0) {
      throw new \InvalidArgumentException('Negative size is not supported');
    }

    if (ftruncate($this->stream, $size) === TRUE) {
      return $this;
    }

    throw new \RuntimeException('ftruncate() failed');
  }

}
