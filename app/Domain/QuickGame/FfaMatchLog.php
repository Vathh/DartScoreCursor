<?php

namespace App\Domain\QuickGame;

/**
 * dartLog jest czyszczony po legu — matchLog trzyma całą sesję pod kolektor kariery.
 */
final class FfaMatchLog
{
    /**
     * @param  array<string, mixed>  $state
     * @return list<array<string, mixed>>
     */
    public static function merged(array $state): array
    {
        $match = is_array($state['matchLog'] ?? null) ? $state['matchLog'] : [];
        $dart = is_array($state['dartLog'] ?? null) ? $state['dartLog'] : [];

        return array_values(array_merge($match, $dart));
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function archive(array &$state): void
    {
        $state['matchLog'] = self::merged($state);
        $state['dartLog'] = [];
    }
}
