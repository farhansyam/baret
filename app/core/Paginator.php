<?php
declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    public readonly int $pages;
    public readonly int $offset;

    public function __construct(public readonly int $total, public readonly int $perPage, public readonly int $page)
    {
        $this->pages = max(1, (int) ceil($total / max(1, $perPage)));
        $this->offset = (min(max(1, $page), $this->pages) - 1) * $perPage;
    }

    public static function fromRequest(int $total, ?int $perPage = null): self
    {
        return new self($total, $perPage ?? (int) config('per_page', 15), max(1, (int) ($_GET['page'] ?? 1)));
    }

    public function current(): int
    {
        return min(max(1, $this->page), $this->pages);
    }

    public function from(): int
    {
        return $this->total ? $this->offset + 1 : 0;
    }

    public function to(): int
    {
        return min($this->total, $this->offset + $this->perPage);
    }

    /** SQL LIMIT aman (integer). */
    public function limitSql(): string
    {
        return sprintf(' LIMIT %d OFFSET %d', $this->perPage, $this->offset);
    }

    public function url(int $page): string
    {
        $q = $_GET;
        $q['page'] = $page;
        return '?' . http_build_query($q);
    }

    /** Nomor halaman yang ditampilkan, dengan null sebagai "…". */
    public function window(): array
    {
        $cur = $this->current();
        $out = [];
        for ($i = 1; $i <= $this->pages; $i++) {
            if ($i === 1 || $i === $this->pages || abs($i - $cur) <= 1) {
                $out[] = $i;
            } elseif (end($out) !== null) {
                $out[] = null;
            }
        }
        return $out;
    }
}
