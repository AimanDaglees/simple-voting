<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Exception;

/**
 * Raised when a vote payload or target is invalid.
 */
final class InvalidVoteException extends \InvalidArgumentException {}
