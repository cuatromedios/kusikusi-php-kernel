<?php

namespace Cuatromedios\Kusikusi\Providers;

use App\Models\User;
use App\Models\Entity;
use Cuatromedios\Kusikusi\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Cuatromedios\Kusikusi\Models\Authtoken;
use Cuatromedios\Kusikusi\Models\Activity;

class AuthServiceProvider extends ServiceProvider
{

    const READ_ENTITY = 'read-entity';
    const READ_ALL = 'read-all';
    const WRITE_ENTITY = 'write-entity';
    const LOGIN = 'login';

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
        // New permissions: read and write
        Gate::define(self::READ_ENTITY, function ($user, $entity_id, $subaction = NULL, $metadata = NULL) {
            $entity = Entity::where("id", $entity_id)->withTrashed()->firstOrFail();
            foreach ($user->permissions as $permission) {
                $isSelfOrDescendant = Entity::isSelfOrDescendant($entity_id, $permission->entity_id);
                if ($isSelfOrDescendant && ($permission->read === Permission::ANY || ($permission->read === Permission::OWN && $entity->created_by === $user->id))) {
                    return true;
                }
            }
            Activity::add($user->id, $entity_id, self::READ_ENTITY, FALSE, $subaction, $metadata);
            return false;
        });
        Gate::define(self::READ_ALL, function ($user, $subaction = NULL, $metadata = NULL) {
            foreach ($user->permissions as $permission) {
                if ($permission->read === Permission::ANY) {
                    return true;
                }
            }
            Activity::add($user->id, '', self::READ_ALL, FALSE, $subaction, $metadata);
            return false;
        });
        Gate::define(self::WRITE_ENTITY, function ($user, $entity_id, $subaction = NULL, $metadata = NULL) {
            $entity = Entity::where("id", $entity_id)->withTrashed()->firstOrFail();
            foreach ($user->permissions as $permission) {
                $isSelfOrDescendant = Entity::isSelfOrDescendant($entity_id, $permission->entity_id);
                if ($isSelfOrDescendant && ($permission->write === Permission::ANY || ($permission->write === Permission::OWN && $entity->created_by === $user->id))) {
                    return true;
                }
            }
            Activity::add($user->id, $entity_id, self::WRITE_ENTITY, FALSE, $subaction, $metadata);
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
//                    $request->request->add(['user_id' => $user->entity_id]);
//                    $request->request->add(['user_profile' => $user->profile]);
                }
                return $user;
            } else {
                return NULL;
            }
        });
    }
}
