<?php

namespace OpenDominion\Sharp\Entities\Dominion;

use Code16\Sharp\Form\Fields\SharpFormTextField;
use Code16\Sharp\Form\Layout\FormLayoutColumn;
use Code16\Sharp\Form\SharpForm;
use OpenDominion\Models\Dominion;

class DominionSharpForm extends SharpForm
{
    public function find($id): array
    {
        return $this->transform(Dominion::find($id));
    }

    public function update($id, array $data)
    {
        dd([$id, $data]);
        // TODO: Implement update() method.
    }

    public function delete($id)
    {
        dd($id);
        // TODO: Implement delete() method.
    }

    public function buildFormFields()
    {
        $this->addField(
            SharpFormTextField::make('name')
                ->setLabel('Name')
        );
    }

    public function buildFormLayout()
    {
        $this->addColumn(6, function (FormLayoutColumn $column) {
            $column->withSingleField('name');
        });
    }
}
