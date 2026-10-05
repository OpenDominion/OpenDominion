<?php

namespace OpenDominion\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\RoundMutationService;
use OpenDominion\Services\Dominion\SelectorService;
use Symfony\Component\HttpFoundation\Response;

class CoordinateRoundMutation
{
    public function __construct(public RoundMutationService $mutations, public SelectorService $selector)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() && !($request->route()->defaults['mutates_round'] ?? false)) {
            return $next($request);
        }

        try {
            $round = $request->route('round');
            if ($round instanceof Round) {
                return $this->mutations->run($round, function (Round $lockedRound) use ($request, $next) {
                    $this->refreshRouteModels($request, $lockedRound);
                    $request->route()->setParameter('round', $lockedRound);
                    return $this->handleMutationRequest($request, $next);
                });
            }

            if (!$this->selector->hasUserSelectedDominion()) {
                $this->selector->tryAutoSelectDominionForAuthUser();
            }
            $dominionId = session(SelectorService::SESSION_NAME);
            if ($dominionId === null) {
                return $next($request);
            }

            $dominion = Dominion::query()->find($dominionId, ['id', 'round_id']);
            if ($dominion === null) {
                return $next($request);
            }

            return $this->mutations->runForDominion($dominion, function (Dominion $lockedDominion) use ($request, $next) {
                $this->refreshRouteModels($request, $lockedDominion->round, $lockedDominion);
                $this->selector->useLockedDominion($lockedDominion);
                return $this->handleMutationRequest($request, $next);
            }, loadGameRelations: true);
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        } catch (GameException $exception) {
            return redirect()->back()->withInput($request->all())->withErrors([$exception->getMessage()]);
        }
    }

    protected function handleMutationRequest(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($response->getStatusCode() >= 400) {
            throw new HttpResponseException($response);
        }

        return $response;
    }

    protected function refreshRouteModels(Request $request, Round $round, ?Dominion $dominion = null): void
    {
        foreach ($request->route()->parameters() as $name => $parameter) {
            if ($parameter instanceof Round && $parameter->is($round)) {
                $request->route()->setParameter($name, $round);
            } elseif ($parameter instanceof Dominion && $dominion !== null && $parameter->is($dominion)) {
                $request->route()->setParameter($name, $dominion);
            } elseif ($parameter instanceof Model) {
                $parameter->refresh();
            }
        }
    }
}
