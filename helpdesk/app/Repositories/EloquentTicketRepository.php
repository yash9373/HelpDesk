<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EloquentTicketRepository implements TicketRepositoryInterface
{
    /**
     * Shortlist candidates to avoid brute-force scanning.
     * - Ignore closed tickets
     * - Prefer same category
     * - Limit to last $days (default 365)
     * - Prefilter by tokens in subject/description, but relax to subject-only if few results
     * - Cap the result set by $limit (default 200)
     */
    public function shortlist(array $tokens, ?string $category = null, int $days = 365, int $limit = 200): Collection
    {
        $query = Ticket::query();
        $query->where('status', '!=', Ticket::STATUS_CLOSED);
        if ($category) {
            $query->where('category', $category);
        }
        $from = Carbon::now()->subDays($days);
        $query->where('created_at', '>=', $from);
        $query->where(function ($q) use ($tokens) {
            foreach ($tokens as $t) {
                $tok = "%" . Str::lower($t) . "%";
                $q->orWhereRaw('LOWER(subject) LIKE ?', [$tok]);
                $q->orWhereRaw('LOWER(description) LIKE ?', [$tok]);
            }
        });

        // If query returns too few results, relax by searching only subject
        $count = $query->count();
        if ($count < 10) {
            $query = Ticket::query();
            $query->where('status', '!=', Ticket::STATUS_CLOSED);
            if ($category) $query->where('category', $category);
            $query->where('created_at', '>=', $from);
            $query->where(function ($q) use ($tokens) {
                foreach ($tokens as $t) {
                    $tok = "%" . Str::lower($t) . "%";
                    $q->orWhereRaw('LOWER(subject) LIKE ?', [$tok]);
                }
            });
        }
        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    public function find(int $id)
    {
        return Ticket::find($id);
    }

    public function create(array $data)
    {
        return Ticket::create($data);
    }
}
