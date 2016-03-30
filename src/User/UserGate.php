<?php
namespace Polyether\User;


use Backend;
use Cache;
use Polyether\Support\EtherError;
use Polyether\User\Repositories\UserRepository;
use Validator;

class UserGate
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function find($userId, $columns = ['*'])
    {
        $cache_key = (is_array($columns) && $columns[ 0 ] == '*') ? 'user_' . $userId : 'user_' . md5($userId . http_build_query($columns));

        // See if we've the post cached earlier in this request and return it if it's available
        if (Cache::tags(['users', 'user_' . $userId])->has($cache_key)) {
            return Cache::tags(['users', 'user_' . $userId])->get($cache_key);
        } else {
            // Eventually we try to fetch the post from the database or return an error
            try {
                $user = $this->userRepository->findOrFail($userId, $columns);
                // Cache it using the caching system
                Cache::tags(['users', 'user_' . $userId])
                     ->put($cache_key, $user, \Option::get('users_cache_expires', 60));

                return $user;
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function create($userArr)
    {
        $validator = Validator::make($userArr, [
            'first_name' => 'required|min:3|max:255',
            'last_name'  => 'required|min:3|max:255',
            'username'   => 'required|min:3|max:32|unique:users',
            'email'      => 'required|email|max:255|unique:users',
            'password'   => 'required|min:6|max:64',
        ]);

        if ($validator->fails()) {
            return new EtherError($validator->getMessageBag()->getMessages());
        }

        $userArr[ 'password' ] = bcrypt($userArr[ 'password' ]);

        return $this->userRepository->create($userArr);
    }

    public function delete($userId)
    {
        return $this->userRepository->delete($userId);
    }

    public function onBoot()
    {
        $this->_registerMenuPages();
    }

    private function _registerMenuPages()
    {
        \Plugin::add_action('init_backend_menu', function () {
            $slug = 'users';
            $title = 'Users';
            $permissions = ['create_users', 'view_users', 'edit_users', 'delete_users '];
            $link = route('user_manage');
            $icon = 'fa fa-user';

            $parent_slug = Backend::registerMenuPage($slug, $title, $permissions, $link, $icon, 20);

            if ($parent_slug) {
                $singularName = 'User';
                $title = 'Add new ' . $singularName;
                $link = route('user_create');
                $icon = 'fa fa-plus';
                Backend::registerMenuSubPage($parent_slug . '_add', $parent_slug, $title, [], $link, $icon);
            }
        });
    }

    public function update($userId, $userArr)
    {
        $allowOnly = ['first_name', 'last_name', 'email', 'enabled'];
        $userArr = array_intersect_key($userArr, array_flip($allowOnly));

        try {
            $user = $this->userRepository->update($userArr, $userId);
        } catch (\Exception $e) {
            return new EtherError($e);
        }

        Cache::tags('user_' . $userId)->flush();

        return $user;
    }
}