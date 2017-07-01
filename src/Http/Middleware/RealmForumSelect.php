<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use OpenDominion\Contracts\Council\ForumServiceContract;

class RealmForumSelect
{
    protected $forum;

    /**
     * RealmForumSelect constructor.
     * @param ForumServiceContract $forum
     */
    public function __construct(ForumServiceContract $forum)
    {
        $this->forum = $forum;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$this->forum->canVisit($request->user(), $request->route())) {
            abort(403, 'Access denied');
        }
        return $next($request);
    }
}
