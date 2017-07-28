<?php

namespace OpenDominion\Http\Middleware;

use Auth;
use Closure;
use Config;
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
        if (Auth::guest()) {
            // Use the "frontpage" for guest access to the forum.
            Config::set('chatter.master_file_extend', 'layouts.topnav');
        }
        if (!$this->forum->canVisit(Auth::user(), $request->route())) {
            abort(403, 'Access denied');
        }
        return $next($request);
    }
}
