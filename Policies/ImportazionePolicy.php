<?php namespace Modules\CupChart\Policies;

use App\Models\User;
use App\Models\Importazione;
use Gecche\PolicyBuilder\Facades\PolicyBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportazionePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Importazione  $model
     * @return mixed
     */
    public function view(User $user, Importazione $model)
    {
        //
        if ($user && $user->can('view importazione')) {
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
        if ($user && $user->can('create importazione')) {
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
    public function update(User $user, Importazione $model)
    {
        //
        if ($user && $user->can('edit importazione')) {
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
    public function delete(User $user, Importazione $model)
    {
        //
        if ($user && $user->can('delete importazione')) {
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
        if ($user && $user->can('list importazione')) {
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

//        if ($user && $user->can('view all importazione')) {
//            return Gate::aclAll($builder);
//        }

        if ($user && $user->can('view importazione')) {
            return PolicyBuilder::all($builder,Importazione::class);
        }

        return PolicyBuilder::none($builder,Importazione::class);
    }
}
