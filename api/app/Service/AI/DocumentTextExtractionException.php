<?php

namespace App\Service\AI;

use Exception;

/**
 * Carries a machine readable reason so the editor can explain what to do —
 * "unsupported" and "empty" (a scanned PDF, typically) need different advice.
 */
class DocumentTextExtractionException extends Exception
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
