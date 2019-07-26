<?php

namespace OpenDominion\Sharp\Entities\User;

use Code16\Sharp\Form\Eloquent\Transformers\FormUploadModelTransformer;
use Code16\Sharp\Form\Eloquent\WithSharpFormEloquentUpdater;
use Code16\Sharp\Form\Fields\SharpFormTextField;
use Code16\Sharp\Form\Fields\SharpFormUploadField;
use Code16\Sharp\Form\Layout\FormLayoutColumn;
use Code16\Sharp\Form\SharpForm;
use OpenDominion\Models\User;

class UserSharpForm extends SharpForm
{
    use WithSharpFormEloquentUpdater;

    /**
     * Retrieve a Model for the form and pack all its data as JSON.
     *
     * @param $id
     * @return array
     */
    public function find($id): array
    {
        return $this->transform(
            User::findOrFail($id)
        );
    }

    /**
     * @param $id
     * @param array $data
     * @return mixed the instance id
     */
    public function update($id, array $data)
    {
        $instance = $id ? User::findOrFail($id) : new User;

        return tap($instance, function ($user) use ($data) {
            $this->save($user, $data);
        })->id;
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    /**
     * Build form fields using ->addField()
     *
     * @return void
     */
    public function buildFormFields()
    {
        $this->addField(
            SharpFormTextField::make('display_name')
                ->setLabel('Display Name')
        )->addField(
            SharpFormUploadField::make('avatar')
                ->setLabel('Avatar')
                ->setFileFilterImages()
                ->setStorageDisk('local')
                ->setStorageBasePath('uploads/avatars')
//                ->setReadOnly()
        );

        // activated
        // settings?
        // last_deleted_dominion_round todo:remove
        // last_online
        // created_at
        // updated_at?
    }

    /**
     * Build form layout using ->addTab() or ->addColumn()
     *
     * @return void
     */
    public function buildFormLayout()
    {
        $this->addColumn(6, static function (FormLayoutColumn $column) {
            $column->withSingleField('display_name');
        })->addColumn(6, static function (FormLayoutColumn $column) {
            $column->withSingleField('avatar');
        });
    }
}
