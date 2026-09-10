<?php

namespace App\Domain\Career;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Toczące się okno kariery: N dni wstecz od początku dzisiaj (Europe/Warsaw) albo całość.
 */
final class CareerWindow
{
    public const TIMEZONE = 'Europe/Warsaw';

    public const DEFAULT_KEY = '90d';

    /** @var list<string> */
    public const KEYS = ['30d', '90d', '180d', '365d', 'all'];

    public function __construct(
        public readonly string $key,
        public readonly ?int $days,
    ) {}

    public static function fromQuery(?string $window): self
    {
        $key = $window ?: self::DEFAULT_KEY;
        if (! in_array($key, self::KEYS, true)) {
            throw new InvalidArgumentException('Nieznane okno kariery.');
        }

        $days = $key === 'all' ? null : (int) substr($key, 0, -1);

        return new self($key, $days);
    }

    public function startUtc(): ?CarbonImmutable
    {
        if ($this->days === null) {
            return null;
        }

        return CarbonImmutable::now(self::TIMEZONE)
            ->startOfDay()
            ->subDays($this->days)
            ->utc();
    }

    public function endExclusiveUtc(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)
            ->startOfDay()
            ->addDay()
            ->utc();
    }

    /**
     * Poprzednie okno tej samej długości (dla delty). Brak dla „całość”.
     */
    public function previous(): ?self
    {
        if ($this->days === null) {
            return null;
        }

        return new self($this->key.'_prev', $this->days);
    }

    public function previousStartUtc(): ?CarbonImmutable
    {
        if ($this->days === null) {
            return null;
        }

        return CarbonImmutable::now(self::TIMEZONE)
            ->startOfDay()
            ->subDays($this->days * 2)
            ->utc();
    }

    public function previousEndExclusiveUtc(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)
            ->startOfDay()
            ->subDays($this->days ?? 0)
            ->utc();
    }
}
