<?php

namespace OpenDominion\Services\Dominion;

use BadMethodCallException;
use DB;
use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;

/**
 * Class QueueService
 *
 * @method Collection getConstructionQueue(Dominion $dominion)
 * @method int getConstructionQueueTotal(Dominion $dominion)
 * @method int getConstructionQueueTotalByResource(Dominion $dominion, string $resource)
 * @method Collection getExplorationQueue(Dominion $dominion)
 * @method int getExplorationQueueTotal(Dominion $dominion)
 * @method int getExplorationQueueTotalByResource(Dominion $dominion, string $resource)
 * @method Collection getInvasionQueue(Dominion $dominion)
 * @method int getInvasionQueueTotal(Dominion $dominion)
 * @method int getInvasionQueueTotalByResource(Dominion $dominion, string $resource)
 * @method Collection getTrainingQueue(Dominion $dominion)
 * @method int getTrainingQueueTotal(Dominion $dominion)
 * @method int getTrainingQueueTotalByResource(Dominion $dominion, string $resource)
 */
class QueueService
{
    /** @var array */
    protected $queueCache = [];

    /**
     * Returns the queue of specific type of a dominion.
     *
     * @param string $type
     * @param Dominion $dominion
     * @return Collection
     */
    public function getQueue(string $type, Dominion $dominion): Collection
    {
        $cacheKey = "{$type}.{$dominion->id}";

        if (array_has($this->queueCache, $cacheKey)) {
            return collect(array_get($this->queueCache, $cacheKey));
        }

        $data = DB::table('dominion_queue')->where([
            'dominion_id' => $dominion->id,
            'source' => $type,
        ])->get();

        array_set($this->queueCache, $cacheKey, $data->toArray());

        return $data;
    }

    /**
     * Returns the amount of incoming resource for a specific type and hour of a dominion.
     *
     * @param string $type
     * @param Dominion $dominion
     * @param string $resource
     * @param int $hour
     * @return int
     */
    public function getQueueAmount(string $type, Dominion $dominion, string $resource, int $hour): int
    {
        return $this->getQueue($type, $dominion)
                ->filter(function ($row) use ($resource, $hour) {
                    return (
                        ($row->resource === $resource) &&
                        ($row->hours === $hour)
                    );
                })->first()->amount ?? 0;
    }

    /**
     * Returns the sum of resources in a queue of a specific type of a
     * dominion.
     *
     * @param string $type
     * @param Dominion $dominion
     * @return int
     */
    public function getQueueTotal(string $type, Dominion $dominion): int
    {
        return $this->getQueue($type, $dominion)
            ->sum('amount');
    }

    /**
     * Returns the sum of a specific resource in a queue of a specific type of
     * a dominion.
     *
     * @param string $type
     * @param Dominion $dominion
     * @param string $resource
     * @return int
     */
    public function getQueueTotalByResource(string $type, Dominion $dominion, string $resource): int
    {
        return $this->getQueue($type, $dominion)
            ->filter(function ($row) use ($resource) {
                return ($row->resource === $resource);
            })->sum('amount');
    }

    /**
     * Queues new resources for a dominion.
     *
     * @param string $type
     * @param Dominion $dominion
     * @param array $data In format: [$resource => $amount, $resource2 => $amount2] etc
     * @param int $hours
     */
    public function queueResources(string $type, Dominion $dominion, array $data, int $hours = 12): void
    {
        $data = array_map('\intval', $data);
        $now = now();

        foreach ($data as $resource => $amount) {
            if ($amount === 0) {
                continue;
            }

            $existingQueueRow = $this->getQueue($type, $dominion)->filter(function ($row) use ($resource, $hours) {
                return (
                    ($row->resource === $resource) &&
                    ($row->hours === $hours)
                );
            })->first();

            if ($existingQueueRow === null) {
                DB::table('dominion_queue')->insert([
                    'dominion_id' => $dominion->id,
                    'source' => $type,
                    'resource' => $resource,
                    'hours' => $hours,
                    'amount' => $amount,
                    'created_at' => $now,
                ]);

            } else {
                DB::table('dominion_queue')->update([
                    'amount' => ($existingQueueRow->amount + $amount),
                ]);
            }
        }
    }

    /**
     * Helper getter to call queue methods with types specified in the method
     * name.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        preg_match_all('/((?:^|[A-Z])[a-z]+)/', $name, $matches);
        $methodParts = $matches[1];

        if (!((array_get($methodParts, '0') === 'get') && (array_get($methodParts, '2') === 'Queue'))) {
            throw new BadMethodCallException(sprintf(
                'Method %s->%s does not exist.', static::class, $name
            ));
        }

        $type = strtolower(array_get($methodParts, '1'));
        $method = implode('', array_except($methodParts, '1'));
        array_unshift($arguments, $type);

        return \call_user_func_array([$this, $method], $arguments);
    }
}