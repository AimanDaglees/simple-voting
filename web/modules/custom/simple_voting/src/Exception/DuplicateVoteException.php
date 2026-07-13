<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Exception;

/**
 * Raised when a user attempts a second vote on the same question.
 */
final class DuplicateVoteException extends \RuntimeException {}
