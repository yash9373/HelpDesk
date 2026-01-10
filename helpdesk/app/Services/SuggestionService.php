<?php

namespace App\Services;

use App\Repositories\TicketRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Str;

class SuggestionService
{
    protected $repo;

    public function __construct(TicketRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Suggest tickets for a partially typed subject.
     *
     * Rules and safeguards:
     * - We never brute-force compare against the whole DB. The repository's shortlist() prefilters
     *   (recent items, same category when available, ignore closed tickets, and limits results).
     * - Default shortlist window: last 365 days, shortlist cap: 200 candidates.
     * - Final scoring mixes token overlap, subject similarity and recency; a low threshold filters noisy matches.
     */
    /**
     * Suggest tickets for a partially typed subject.
     *
     * @param string $subject
     * @param string|null $category
     * @param User|null $user  The requesting user (used to enforce visibility)
     * @param int $limit
     * @return array
     */
    public function suggest(string $subject, ?string $category = null, ?User $user = null, int $limit = 5): array
    {
        $tokens = TextNormalizer::tokens($subject);
        if (empty($tokens)) {
            return [];
        }

        // Shortlist handled by repository (see EloquentTicketRepository::shortlist)
        $candidates = $this->repo->shortlist($tokens, $category, 365, 200);

        // Enforce visibility using the policy: only tickets the requesting user may view are kept
        if ($user) {
            // Use Gate so policy logic (e.g. agent role) is authoritative
            $candidates = $candidates->filter(fn($c) => \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $c))->values();
        }

        $ranked = [];
        foreach ($candidates as $c) {
            $candidateTokens = TextNormalizer::tokens($c->subject . ' ' . $c->description);
            $overlap = $this->overlapScore($tokens, $candidateTokens);
            $similarity = $this->similarityScore($subject, $c->subject);
            $recency = $this->recencyScore($c->created_at->diffInDays(now()));
            $score = 0.5 * $overlap + 0.3 * $similarity + 0.2 * $recency;
            if ($score >= 0.15) {
                $ranked[] = [
                    'ticket' => ['id' => $c->id, 'subject' => $c->subject, 'description' => $c->description, 'category' => $c->category, 'severity' => $c->severity],
                    'snippet' => Str::limit($c->description, 140),
                    'score' => round($score, 3),
                ];
            }
        }
        usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($ranked, 0, $limit);
    }

    protected function overlapScore(array $a, array $b): float
    {
        if (empty($a) || empty($b)) {
            return 0.0;
        }
        $i = array_intersect($a, $b);
        return count($i) / max(1, count($a));
    }

    protected function similarityScore(string $s1, string $s2): float
    {
        $len = max(mb_strlen($s1), mb_strlen($s2));
        if ($len === 0) return 0.0;
        similar_text($s1, $s2, $percent);
        return $percent / 100.0;
    }

    protected function recencyScore(int $ageDays): float
    {
        return 1.0 / (1.0 + $ageDays / 30.0);
    }
}
