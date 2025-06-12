<?php

namespace OpenDominion\Services\Dominion;

use BadMethodCallException;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use Throwable;

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
    /** @var bool */
    protected $forTick = false;

    /**
     * Toggle if this calculator should include the following hour's resources.
     */
    public function setForTick(bool $value)
    {
        $this->forTick = $value;
    }

    /**
     * Returns the queue of specific type of a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @return Collection
     */
    public function getQueue(string $source, Dominion $dominion): Collection
    {
        $hours = 0;
        if ($this->forTick) {
            // don't include next hour when calculating tick
            $hours = 1;
        }
        return $dominion->queues
            ->where('source', $source)
            ->where('hours', '>', $hours);
    }

    /**
     * Returns the queue of a specific type of prefixed resource of a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @param string $prefix
     * @return int
     */
    public function getQueueByPrefix(string $source, Dominion $dominion, string $prefix): Collection
    {
        return $this->getQueue($source, $dominion)
            ->filter(static function ($row) use ($prefix) {
                return starts_with($row->resource, $prefix);
            });
    }

    /**
     * Returns the amount of incoming resource for a specific type and hour of a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @param string $resource
     * @param int $hour
     * @return int
     */
    public function getQueueAmount(string $source, Dominion $dominion, string $resource, int $hour): int
    {
        return $this->getQueue($source, $dominion)
                ->filter(static function ($row) use ($resource, $hour) {
                    return (
                        ($row->resource === $resource) &&
                        ($row->hours === $hour)
                    );
                })->first()->amount ?? 0;
    }

    /**
     * Returns the sum of resources in a queue of a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @return int
     */
    public function getQueueTotal(string $source, Dominion $dominion): int
    {
        return $this->getQueue($source, $dominion)
            ->sum('amount');
    }

    /**
     * Returns the sum of a specific resource in a queue of a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @param string $resource
     * @return int
     */
    public function getQueueTotalByResource(string $source, Dominion $dominion, string $resource): int
    {
        return $this->getQueue($source, $dominion)
            ->filter(static function ($row) use ($resource) {
                return ($row->resource === $resource);
            })->sum('amount');
    }

    /**
     * Returns the sum of a specific type of prefixed resource in a queue of a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @param string $prefix
     * @return int
     */
    public function getQueueTotalByPrefix(string $source, Dominion $dominion, string $prefix): int
    {
        return $this->getQueueByPrefix($source, $dominion, $prefix)
            ->sum('amount');
    }

    public function dequeueResource(string $source, Dominion $dominion, string $resource, int $amount): void
    {
        $queue = $this->getQueue($source, $dominion)
            ->filter(static function ($row) use ($resource) {
                return ($row->resource === $resource);
            })->sortByDesc('hours');

        $leftToDequeue = $amount;

        foreach ($queue as $value) {
            $amountEnqueued = $value->amount;
            $amountDequeued = $leftToDequeue;

            if($amountEnqueued < $leftToDequeue) {
                $amountDequeued = $amountEnqueued;
            }

            $leftToDequeue -= $amountDequeued;
            $newAmount = $amountEnqueued - $amountDequeued;

            if($newAmount == 0) {
                $dominion->queues()->where([
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $value->hours,
                ])->delete();
            } else {
                $dominion->queues()->where([
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $value->hours,
                ])->update([
                    'amount' => $newAmount,
                ]);
            }
        }
    }

    /**
     * Queues new resources for a dominion.
     *
     * @param string $source
     * @param Dominion $dominion
     * @param array $data In format: [$resource => $amount, $resource2 => $amount2] etc
     * @param int $hours
     */
    public function queueResources(string $source, Dominion $dominion, array $data, int $hours = 12): void
    {
        $data = array_map('\intval', $data);
        $now = now();

        foreach ($data as $resource => $amount) {
            if ($amount === 0) {
                continue;
            }
            $q = $this->getQueue($source, $dominion);
            $existingQueueRow =
                $q->filter(static function ($row) use ($resource, $hours) {
                    return (
                        ($row->resource === $resource) &&
                        ((int)$row->hours === $hours)
                    );
                })->first();

            if ($existingQueueRow === null) {
                try {
                    $dominion->queues()->insert([
                        'dominion_id' => $dominion->id,
                        'source' => $source,
                        'resource' => $resource,
                        'hours' => $hours,
                        'amount' => $amount,
                        'created_at' => $now,
                    ]);
                } catch(QueryException $e) {
                    if ($e->getCode() == "23000") {
                        $existingQueueRow = true;
                    } else {
                        throw $e;
                    }
                }
            }

            if ($existingQueueRow !== null) {
                $dominion->queues()->where([
                    'source' => $source,
                    'resource' => $resource,
                    'hours' => $hours,
                ])->update([
                    'amount' => DB::raw("amount + $amount"),
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
                'Method %s->%s does not exist.',
                static::class,
                $name
            ));
        }

        $source = strtolower(array_get($methodParts, '1'));
        $method = implode('', array_except($methodParts, '1'));
        array_unshift($arguments, $source);

        return \call_user_func_array([$this, $method], $arguments);
    }
}
