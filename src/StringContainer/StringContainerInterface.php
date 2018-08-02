<?php

namespace Shiyan\Binary\StringContainer;

/**
 * Interface for string containers.
 */
interface StringContainerInterface {

  /**
   * Returns the current position of the string read/write pointer.
   *
   * @return int
   *   A non-negative integer - the pointer position.
   *
   * @throws \RuntimeException
   *   If telling fails.
   */
  public function tell(): int;

  /**
   * Seeks to a position in the string.
   *
   * Seeking past end is not considered an error.
   *
   * @param int $offset
   *   The offset. A negative value can be used to move backwards through the
   *   string which is useful when SEEK_END is used as the $whence value.
   * @param int $whence
   *   (optional) One of:
   *   - SEEK_SET: Set position equal to $offset bytes.
   *   - SEEK_CUR: Set position to current location plus $offset.
   *   - SEEK_END: Set position to end of string plus $offset.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If $whence is invalid.
   * @throws \RuntimeException
   *   If seeking fails.
   */
  public function seek(int $offset, int $whence = SEEK_SET): StringContainerInterface;

  /**
   * Rewinds back to the beginning of the container.
   *
   * @return static
   *
   * @throws \RuntimeException
   *   If rewinding fails.
   */
  public function rewind(): StringContainerInterface;

  /**
   * Determines whether the end of the container has been reached.
   *
   * @return bool
   *   Returns TRUE if the pointer is at end or a stream error occurs, FALSE
   *   otherwise.
   *
   * @throws \RuntimeException
   *   If determination fails.
   */
  public function eof(): bool;

  /**
   * Reads from the container.
   *
   * @param int $length
   *   The number of bytes to read. Reading will stop after $length bytes have
   *   been read or the end of the container is reached, whichever comes first.
   *
   * @return string
   *   The string read.
   *
   * @throws \InvalidArgumentException
   *   If $length is negative.
   * @throws \RuntimeException
   *   If the underlying string cannot be fully read.
   */
  public function read(int $length): string;

  /**
   * Gets a character.
   *
   * @return string
   *   A string containing a single byte read.
   *
   * @throws \RuntimeException
   *   If the end of the container has been reached or reading fails.
   */
  public function getc(): string;

  /**
   * Writes to the container.
   *
   * @param string $string
   *   The string to be written.
   * @param int $length
   *   (optional) If the argument is given, writing will stop after $length
   *   bytes have been written or the end of $string is reached, whichever comes
   *   first.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If $length is negative.
   * @throws \RuntimeException
   *   If the $string cannot be fully written.
   */
  public function write(string $string, int $length = NULL): StringContainerInterface;

  /**
   * Forces a write of the buffered data.
   *
   * @return static
   *
   * @throws \RuntimeException
   *   If flushing fails.
   */
  public function flush(): StringContainerInterface;

  /**
   * Truncates the underlying string to a given length.
   *
   * The read/write pointer is not changed during this operation.
   *
   * @param int $size
   *   The size to truncate to. If size is larger than the string, it is
   *   extended with null bytes. If size is smaller, the extra data will be
   *   lost.
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   *   If $size is negative.
   * @throws \RuntimeException
   *   If truncating fails.
   */
  public function truncate(int $size): StringContainerInterface;

}
