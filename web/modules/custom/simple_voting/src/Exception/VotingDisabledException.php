<?php

declare(strict_types=1);

namespace Drupal\simple_voting\Exception;

/**
 * Raised when the global voting flow is disabled.
 */
final class VotingDisabledException extends \RuntimeException {}
