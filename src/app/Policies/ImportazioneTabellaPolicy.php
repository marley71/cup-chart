<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ImportazioneTabella;
use Gecche\PolicyBuilder\Facades\PolicyBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportazioneTabellaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ImportazioneTabella  $model
     * @return mixed
     */
    public function view(User $user, ImportazioneTabella $model)
    {
        //
        if ($user && $user->can('view importazione_tabella')) {
            return true;
        }

        return false;

    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
        if ($user && $user->can('create importazione_tabella')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Deal  $model
     * @return mixed
     */
    public function update(User $user, ImportazioneTabella $model)
    {
        //
        if ($user && $user->can('edit importazione_tabella')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Deal  $model
     * @return mixed
     */
    public function delete(User $user, ImportazioneTabella $model)
    {
        //
        if ($user && $user->can('delete importazione_tabella')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can access to the listing of the models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function listing(User $user)
    {
        //
        if ($user && $user->can('list importazione_tabella')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can access to the listing of the models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function acl(User $user, $builder)
    {

//        if ($user && $user->can('view all importazione_tabella')) {
//            return Gate::aclAll($builder);
//        }

        if ($user && $user->can('view importazione_tabella')) {
            return PolicyBuilder::all($builder,ImportazioneTabella::class);
        }

        return PolicyBuilder::none($builder,ImportazioneTabella::class);
    }
}
