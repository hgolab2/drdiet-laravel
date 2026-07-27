<?php

namespace App\Exceptions\Firebase;

class FirebaseMessagingException extends FirebaseException
{
    public function __construct(
        string $message,
        private readonly ?string $firebaseStatus = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the Firebase error status returned by the API.
     */
    public function firebaseStatus(): ?string
    {
        return $this->firebaseStatus;
    }

    /**
     * Determine whether this failure means the token is no longer usable.
     */
    public function invalidToken(): bool
    {
        return in_array($this->firebaseStatus, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true);
    }
}