<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface TicketRepositoryInterface
{
    public function shortlist(array $tokens, ?string $category = null, int $days = 365, int $limit = 200): Collection;
    public function find(int $id);
    public function create(array $data);
}
