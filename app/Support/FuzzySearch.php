<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FuzzySearch
{
    public static function normalize(?string $value): string
    {
        return (string) Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }

    /**
     * @param  list<string|null>  $haystacks
     */
    public static function score(array $haystacks, string $query): ?int
    {
        $queryTokens = self::tokens($query);

        if ($queryTokens === []) {
            return 0;
        }

        $normalizedHaystacks = collect($haystacks)
            ->map(fn (?string $value) => self::normalize($value))
            ->filter()
            ->values();

        if ($normalizedHaystacks->isEmpty()) {
            return null;
        }

        $score = 0;

        foreach ($queryTokens as $token) {
            $tokenScore = self::bestTokenScore($token, $normalizedHaystacks);

            if ($tokenScore === null) {
                return null;
            }

            $score += $tokenScore;
        }

        return $score;
    }

    public static function highlightHtml(string $text, string $query): string
    {
        $plainText = strip_tags($text);
        $matches = self::highlightMatches($plainText, $query);

        if ($matches === []) {
            return e($plainText);
        }

        $html = '';
        $cursor = 0;

        foreach ($matches as $match) {
            $start = $match['start'];
            $end = $match['end'];

            $html .= e(mb_substr($plainText, $cursor, $start - $cursor));
            $html .= '<mark>'.e(mb_substr($plainText, $start, $end - $start)).'</mark>';
            $cursor = $end;
        }

        $html .= e(mb_substr($plainText, $cursor));

        return $html;
    }

    /**
     * @return list<string>
     */
    private static function tokens(string $text): array
    {
        return array_values(array_filter(explode(' ', self::normalize($text))));
    }

    /**
     * @param  Collection<int, string>  $haystacks
     */
    private static function bestTokenScore(string $token, Collection $haystacks): ?int
    {
        $bestScore = null;

        foreach ($haystacks as $haystack) {
            $candidateScore = self::scoreAgainstHaystack($token, $haystack);

            if ($candidateScore === null) {
                continue;
            }

            if ($bestScore === null || $candidateScore > $bestScore) {
                $bestScore = $candidateScore;
            }
        }

        return $bestScore;
    }

    private static function scoreAgainstHaystack(string $token, string $haystack): ?int
    {
        if ($haystack === $token) {
            return 180;
        }

        if (str_starts_with($haystack, $token)) {
            return 150;
        }

        if (str_contains($haystack, ' '.$token)) {
            return 135;
        }

        if (str_contains($haystack, $token)) {
            return 120;
        }

        foreach (self::tokens($haystack) as $haystackToken) {
            if (str_starts_with($haystackToken, $token)) {
                return 145;
            }

            if (str_contains($haystackToken, $token)) {
                return 110;
            }

            $distance = levenshtein($token, $haystackToken);
            $maxLength = max(strlen($token), strlen($haystackToken));

            if ($maxLength >= 4 && $distance <= 1) {
                return 92;
            }

            if ($maxLength >= 6 && $distance === 2) {
                return 78;
            }
        }

        if (self::isSubsequence($token, str_replace(' ', '', $haystack))) {
            return 60;
        }

        return null;
    }

    private static function isSubsequence(string $needle, string $haystack): bool
    {
        $position = 0;

        for ($index = 0; $index < strlen($needle); $index++) {
            $found = strpos($haystack, $needle[$index], $position);

            if ($found === false) {
                return false;
            }

            $position = $found + 1;
        }

        return true;
    }

    /**
     * @return array<int, array{start:int,end:int}>
     */
    private static function highlightMatches(string $text, string $query): array
    {
        $normalizedText = self::normalize($text);
        $matches = [];

        foreach (self::tokens($query) as $token) {
            if (strlen($token) < 2) {
                continue;
            }

            $position = strpos($normalizedText, $token);

            if ($position === false) {
                continue;
            }

            $matches[] = [
                'start' => $position,
                'end' => $position + strlen($token),
            ];
        }

        return self::mergeMatches($matches);
    }

    /**
     * @param  array<int, array{start:int,end:int}>  $matches
     * @return array<int, array{start:int,end:int}>
     */
    private static function mergeMatches(array $matches): array
    {
        if ($matches === []) {
            return [];
        }

        usort($matches, fn (array $left, array $right) => $left['start'] <=> $right['start']);

        $merged = [$matches[0]];

        foreach (array_slice($matches, 1) as $match) {
            $lastIndex = array_key_last($merged);
            $last = $merged[$lastIndex];

            if ($match['start'] <= $last['end']) {
                $merged[$lastIndex]['end'] = max($last['end'], $match['end']);

                continue;
            }

            $merged[] = $match;
        }

        return $merged;
    }
}
