<?php

declare(strict_types=1);

use App\Core\Csrf;

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_count(int $n): string
{
    if ($n >= 1_000_000) {
        return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.') . 'M';
    }
    if ($n >= 1_000) {
        return rtrim(rtrim(number_format($n / 1_000, 1), '0'), '.') . 'k';
    }
    return (string)$n;
}

function initials(string $name): string
{
    $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
    if ($name === '') {
        return '??';
    }

    $parts = explode(' ', $name);
    $first = mb_substr($parts[0], 0, 1);
    $last = mb_substr($parts[count($parts) - 1], 0, 1);

    $out = mb_strtoupper($first . $last);
    return $out !== '' ? $out : '??';
}

function time_ago(?string $datetime): string
{
    if ($datetime === null || trim($datetime) === '') {
        return '';
    }

    try {
        $then = new DateTimeImmutable($datetime);
    } catch (Throwable $e) {
        return '';
    }

    $now = new DateTimeImmutable('now', $then->getTimezone());
    $diffSeconds = $now->getTimestamp() - $then->getTimestamp();

    if ($diffSeconds < 0) {
        $diffSeconds = 0;
    }

    if ($diffSeconds < 60) {
        return 'just now';
    }

    $minutes = (int)floor($diffSeconds / 60);
    if ($minutes < 60) {
        return $minutes . 'm ago';
    }

    $hours = (int)floor($minutes / 60);
    if ($hours < 24) {
        return $hours . 'h ago';
    }

    $days = (int)floor($hours / 24);
    if ($days === 1) {
        return 'Yesterday';
    }
    if ($days < 7) {
        return $days . ' days ago';
    }

    return $then->format('M j, Y');
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}
