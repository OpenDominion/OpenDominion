<?php

namespace OpenDominion\Sharp\Entities\User;

use Code16\Sharp\EntityList\Containers\EntityListDataContainer;
use Code16\Sharp\EntityList\EntityListQueryParams;
use Code16\Sharp\EntityList\SharpEntityList;
use OpenDominion\Models\User;

class UserSharpList extends SharpEntityList
{
    /**
     * Retrieve all rows data as array.
     *
     * @param EntityListQueryParams $params
     * @return array
     */
    public function getListData(EntityListQueryParams $params)
    {
        $query = User::query()
            ->orderBy($params->sortedBy(), $params->sortedDir());

        collect($params->searchWords())
            ->each(static function ($word) use ($query) {
                $query->where('display_name', 'like', $word);
            });

        return $this
            ->setCustomTransformer('last_online', static function (?string $lastOnline) {
                if ($lastOnline === null) {
                    return 'Never';
                }

                return carbon($lastOnline)->diffForHumans();
            })
            ->setCustomTransformer('activated', static function (int $activated) {
                return ($activated ? 'Yes' : 'No');
            })
            ->setCustomTransformer('created_at', static function (string $createdAt) {
                return carbon($createdAt)->diffForHumans();
            })
            ->transform($query->paginate(10));
    }

    /**
     * Build list containers using ->addDataContainer()
     *
     * @return void
     */
    public function buildListDataContainers()
    {
        $this->addDataContainer(
            EntityListDataContainer::make('id')
                ->setLabel('Id')
                ->setSortable()
        )->addDataContainer(
            EntityListDataContainer::make('display_name')
                ->setLabel('Display Name')
                ->setSortable()
        )->addDataContainer(
            EntityListDataContainer::make('activated')
                ->setLabel('Activated?')
        )->addDataContainer(
            EntityListDataContainer::make('last_online')
                ->setLabel('Last Online')
                ->setSortable()
        )->addDataContainer(
            EntityListDataContainer::make('created_at')
                ->setLabel('Created At')
                ->setSortable()
        );
    }

    /**
     * Build list layout using ->addColumn()
     *
     * @return void
     */
    public function buildListLayout()
    {
        $this->addColumn('id', 1)
            ->addColumn('display_name', 6)
            ->addColumn('activated', 1)
            ->addColumn('last_online', 2)
            ->addColumn('created_at', 2);
    }

    /**
     * Build list config
     *
     * @return void
     */
    public function buildListConfig()
    {
        $this->setDefaultSort('id')
            ->setPaginated()
            ->setSearchable();
    }
}
