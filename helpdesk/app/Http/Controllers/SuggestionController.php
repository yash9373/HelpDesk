<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SuggestionService;

class SuggestionController extends Controller
{
    protected $svc;

    public function __construct(SuggestionService $svc)
    {
        // Route already applies auth middleware; avoid calling middleware() directly here to remain compatible
        $this->svc = $svc;
    }

    public function index(Request $request)
    {
        $subject = $request->query('subject', '');
        $category = $request->query('category', null);
        $user = $request->user();
        $results = $this->svc->suggest($subject, $category, $user);
        return response()->json($results);
    }
}
