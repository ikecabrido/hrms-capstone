<?php

class LalaContextManager
{
    private const SESSION_KEY = 'lala_context';
    private $sessionStarted;

    public function __construct()
    {
        $this->sessionStarted = session_status() === PHP_SESSION_ACTIVE;
        if (!$this->sessionStarted) {
            session_start();
        }
    }

    public function getContext(): array
    {
        return $_SESSION[self::SESSION_KEY] ?? $this->defaultContext();
    }

    public function setContext(array $context): void
    {
        $_SESSION[self::SESSION_KEY] = $context;
    }

    public function updateContext(array $updates): void
    {
        $current = $this->getContext();
        $current = array_merge($current, $updates);
        $this->setContext($current);
    }

    public function reset(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public function defaultContext(): array
    {
        return [
            'last_intent' => null,
            'last_policy_id' => null,
            'last_policy_code' => null,
            'last_source' => null,
            'last_query' => null,
            'topic' => null,
            'specifics' => [],
        ];
    }
}
