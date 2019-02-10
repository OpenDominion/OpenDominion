<?php

namespace OpenDominion\Sharp\Entities\Dominion;

use Code16\Sharp\Form\Fields\SharpFormTextField;
use Code16\Sharp\Form\Layout\FormLayoutColumn;
use Code16\Sharp\Form\SharpForm;
use OpenDominion\Models\Dominion;

class DominionSharpForm extends SharpForm
{
    function find($id): array
    {
        return $this->transform(Dominion::find($id));
    }

    function update($id, array $data)
    {
        dd([$id, $data]);
        // TODO: Implement update() method.
    }

    function delete($id)
    {
        dd($id);
        // TODO: Implement delete() method.
    }

    function buildFormFields()
    {
        $this->addField(
            SharpFormTextField::make('name')
                ->setLabel('Name')
        );
    }

    function buildFormLayout()
    {
        $this->addColumn(6, function (FormLayoutColumn $column) {
            $column->withSingleField('name');
        });
    }
}
