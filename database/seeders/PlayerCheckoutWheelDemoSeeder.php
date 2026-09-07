<?php

namespace Database\Seeders;

use App\Domain\Badge\BadgeCategory;
use App\Domain\Badge\Checkout\CheckoutCatalog;
use App\Models\Badge\PlayerBadge;
use App\Models\Player\Player;
use App\Models\Users\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Checkout Wheel na profilu Karola — read model pod podgląd UI.
 * Idempotentny: nadpisuje kategorię checkout tego gracza.
 *
 * php artisan db:seed --class=PlayerCheckoutWheelDemoSeeder
 */
class PlayerCheckoutWheelDemoSeeder extends Seeder
{
    private const EMAIL = 'profil@test.pl';

    /**
     * Trafienia dobrane tak, żeby na kole były wszystkie poziomy (Locked→Apex).
     *
     * @var array<int, int>
     */
    private const HITS = [
        170 => 18,
        167 => 15,
        164 => 12,
        161 => 10,
        160 => 11,
        157 => 6,
        150 => 5,
        148 => 4,
        140 => 8,
        136 => 5,
        132 => 7,
        130 => 3,
        128 => 2,
        124 => 4,
        121 => 7,
        120 => 10,
        118 => 3,
        116 => 1,
        112 => 2,
        110 => 6,
        107 => 1,
        104 => 3,
        100 => 9,
        101 => 1,
        108 => 2,
        111 => 1,
        114 => 3,
        117 => 1,
        123 => 2,
        126 => 4,
        129 => 1,
        131 => 2,
        134 => 1,
        138 => 3,
        141 => 1,
        144 => 2,
        146 => 1,
        152 => 3,
        154 => 1,
        156 => 2,
    ];

    public function run(): void
    {
        $user = User::query()->where('email', self::EMAIL)->first();
        $player = $user?->player;
        if ($player === null) {
            throw new RuntimeException(
                'Brak gracza '.self::EMAIL.'. Najpierw: php artisan db:seed --class=PlayerCareerProfileDemoSeeder',
            );
        }

        $this->replaceCheckoutBadges($player);

        $items = count(self::HITS);
        $this->command?->info('Checkout Wheel demo: '.$player->name.' (player_id '.$player->id.')');
        $this->command?->line('  odblokowane: '.$items.' / '.CheckoutCatalog::count());
        $this->command?->line('  profil: '.url('/players/'.$player->id));
    }

    private function replaceCheckoutBadges(Player $player): void
    {
        $category = BadgeCategory::Checkout->value;
        PlayerBadge::query()
            ->where('player_id', $player->id)
            ->where('category', $category)
            ->delete();

        $now = CarbonImmutable::now('Europe/Warsaw');
        foreach (self::HITS as $checkout => $hits) {
            if (! CheckoutCatalog::contains($checkout) || $hits < 1) {
                continue;
            }

            $first = $now->subDays(20 + ($checkout % 40))->setTime(20, 15);
            PlayerBadge::query()->create([
                'player_id' => $player->id,
                'category' => $category,
                'badge_key' => (string) $checkout,
                'times_earned' => $hits,
                'first_unlocked_at' => $first,
                'last_earned_at' => $first->addDays(min(18, $hits)),
                'source_quality' => 'manual',
            ]);
        }
    }
}
