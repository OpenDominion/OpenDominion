<?php

namespace OpenDominion\Sharp\Entities\Dominion;

use Code16\Sharp\EntityList\Containers\EntityListDataContainer;
use Code16\Sharp\EntityList\EntityListQueryParams;
use Code16\Sharp\EntityList\SharpEntityList;
use OpenDominion\Models\Dominion;

class DominionSharpList extends SharpEntityList
{
    public function buildListDataContainers()
    {
        $this->addDataContainer(
            EntityListDataContainer::make('id')
                ->setLabel('Id')
        );
    }

    public function buildListConfig()
    {
        // TODO: Implement buildListConfig() method.
    }

    public function buildListLayout()
    {
        $this->addColumn('id', 12);
    }

    public function getListData(EntityListQueryParams $params)
    {
        return $this->transform(Dominion::all());
    }
}
