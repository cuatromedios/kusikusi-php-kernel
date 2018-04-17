<?php

namespace Cuatromedios\Kusikusi\Providers;

use App\Models\User;
use Cuatromedios\Kusikusi\Models\Entity;
use Cuatromedios\Kusikusi\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Cuatromedios\Kusikusi\Models\Authtoken;
use Cuatromedios\Kusikusi\Models\Activity;

class AuthServiceProvider extends ServiceProvider
{

    const GET_ENTITY = 'get-entity';
    const GET_ALL = 'get-all';
    const POST_ENTITY = 'post-entity';
    const PATCH_ENTITY = 'patch-entity';
    const DELETE_ENTITY = 'delete-entity';

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {

        Gate::define(self::GET_ENTITY, function ($user, $entity_id, $subaction = NULL, $metadata = NULL) {
            $entity = Entity::where("id", $entity_id)->firstOrFail();
            foreach ($user->permissions as $permission) {
                $isSelfOrDescendant = Entity::isSelfOrDescendant($entity_id, $permission->entity_id);
                if ($isSelfOrDescendant && ($permission->get === Permission::ANY || ($permission->get === Permission::OWN && $entity->created_by === $user->entity_id))) {
                    Activity::add($user->entity_id, $entity_id, self::GET_ENTITY, TRUE, $subaction, $metadata);
                    return true;
                }
            }
            Activity::add($user->entity_id, $entity_id, self::GET_ENTITY, FALSE, $subaction, $metadata);
            return false;
        });
        Gate::define(self::GET_ALL, function ($user, $subaction = NULL, $metadata = NULL) {
            foreach ($user->permissions as $permission) {
                if ($permission->get === Permission::ANY && $permission->entity_id === 'root') {
                    Activity::add($user->entity_id, '', self::GET_ALL, TRUE, $subaction, $metadata);
                    return true;
                }
            }
            Activity::add($user->entity_id, '', self::GET_ALL, FALSE, $subaction, $metadata);
            return false;
        });
        Gate::define(self::POST_ENTITY, function ($user, $entity_id, $subaction = NULL, $metadata = NULL) {
            $entity = Entity::where("id", $entity_id)->firstOrFail();
            foreach ($user->permissions as $permission) {
                $isSelfOrDescendant = Entity::isSelfOrDescendant($entity_id, $permission->entity_id);
                if ($isSelfOrDescendant && ($permission->post === Permission::ANY || ($permission->post === Permission::OWN && $entity->created_by === $user->entity_id))) {
                    Activity::add($user->entity_id, $entity_id, self::POST_ENTITY, TRUE, $subaction, $metadata);
                    return true;
                }
            }
            Activity::add($user->entity_id, $entity_id, self::POST_ENTITY, FALSE, $subaction, $metadata);
            return false;
        });
        Gate::define(self::PATCH_ENTITY, function ($user, $entity_id, $subaction = NULL, $metadata = NULL) {
            $entity = Entity::where("id", $entity_id)->firstOrFail();
            foreach ($user->permissions as $permission) {
                $isSelfOrDescendant = Entity::isSelfOrDescendant($entity_id, $permission->entity_id);
                if ($isSelfOrDescendant && ($permission->patch === Permission::ANY || ($permission->patch === Permission::OWN && $entity->created_by === $user->entity_id))) {
                    Activity::add($user->entity_id, $entity_id, self::PATCH_ENTITY, TRUE, $subaction, $metadata);
                    return true;
                }
            }
            Activity::add($user->entity_id, $entity_id, self::PATCH_ENTITY, FALSE, $subaction, $metadata);
            return false;
        });
        Gate::define(self::DELETE_ENTITY, function ($user, $entity_id, $subaction = NULL, $metadata = NULL) {
            $entity = Entity::where("id", $entity_id)->firstOrFail();
            foreach ($user->permissions as $permission) {
                $isSelfOrDescendant = Entity::isSelfOrDescendant($entity_id, $permission->entity_id);
                if ($isSelfOrDescendant && ($permission->delete === Permission::ANY || ($permission->delete === Permission::OWN && $entity->created_by === $user->entity_id))) {
                    Activity::add($user->entity_id, $entity_id, self::DELETE_ENTITY, TRUE, $subaction, $metadata);
                    return true;
                }
            }
            Activity::add($user->entity_id, $entity_id, self::DELETE_ENTITY, FALSE, $subaction, $metadata);
            return false;
        });

        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            $authHeader = env('AUTHORIZATION_HEADER', Authtoken::AUTHORIZATION_HEADER) ;
            if ($request->header($authHeader)) {
                $key = explode(' ',$request->header($authHeader))[1];
                $user = User::whereHas('authtokens', function ($query) use ($key) {
                    $query->where('token', '=', $key);
                })->first();
                if(!empty($user)){
                    $request->request->add(['user_id' => $user->entity_id]);
                    $request->request->add(['user_profile' => $user->profile]);
                }
                return $user;
            } else {
                return NULL;
            }
        });
    }
}
