<?php

declare(strict_types = 1);

namespace Shiyan\Binary\StringContainer;

/**
 * Provides a container for usual strings, making them seekable.
 */
class SeekableString implements StringContainerInterface {

  /**
   * Wrapped string.
   *
   * @var string
   */
  protected $str;

  /**
   * Pointer position.
   *
   * @var int
   */
  protected $pos = 0;

  /**
   * SeekableString constructor.
   *
   * @param string $string
   *   String to wrap.
   */
  public function __construct(string $string) {
    $this->str = $string;
  }

  /**
   * {@inheritdoc}
   */
  public function tell(): int {
    return $this->pos;
  }

  /**
   * {@inheritdoc}
   */
  public function seek(int $offset, int $whence = SEEK_SET): StringContainerInterface {
    switch ($whence) {
      case SEEK_SET:
        $new_pos = $offset;
        break;

      case SEEK_CUR:
        $new_pos = $offset + $this->pos;
        break;

      case SEEK_END:
        $new_pos = $offset + strlen($this->str);
        break;

      default:
        throw new \InvalidArgumentException('Invalid whence value');
    }

    if ($new_pos < 0) {
      throw new \RuntimeException('Cannot seek below zero');
    }

    $this->pos = $new_pos;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): StringContainerInterface {
    $this->pos = 0;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function eof(): bool {
    return $this->pos >= strlen($this->str);
  }

  /**
   * {@inheritdoc}
   */
  public function read(int $length): string {
    if ($length < 0) {
      throw new \InvalidArgumentException('Length parameter must not be negative');
    }
    // Check for EOF explicitly, because substr() would return FALSE.
    if ($length === 0 || $this->eof()) {
      return '';
    }

    $string = substr($this->str, $this->pos, $length);
    $this->pos += strlen($string);
    return $string;
  }

  /**
   * {@inheritdoc}
   */
  public function getc(): string {
    if ($this->eof()) {
      throw new \RuntimeException('Cannot get character after EOF');
    }

    return $this->str[$this->pos++];
  }

  /**
   * {@inheritdoc}
   */
  public function write(string $string, int $length = NULL): StringContainerInterface {
    if ($length < 0) {
      throw new \InvalidArgumentException('Length parameter must not be negative');
    }
    // The $length argument in substr() is not NULL by default, it just either
    // given or not.
    if (isset($length)) {
      $string = substr($string, 0, $length);
    }

    $length = strlen($string);
    $this->str = str_pad($this->str, $this->pos, "\0");
    $this->str = substr_replace($this->str, $string, $this->pos, $length);
    $this->pos += $length;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function flush(): StringContainerInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function truncate(int $size): StringContainerInterface {
    if ($size < 0) {
      throw new \InvalidArgumentException('Negative size is not supported');
    }

    $this->str = str_pad($this->str, $size, "\0");
    $this->str = substr($this->str, 0, $size);
    return $this;
  }

}
