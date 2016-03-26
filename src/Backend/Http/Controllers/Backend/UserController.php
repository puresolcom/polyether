<?php

namespace Polyether\Backend\Http\Controllers\Backend;

use Auth;
use Plugin;
use Polyether\Support\DataTable;
use Polyether\User\Repositories\UserRepository;
use Request;
use UserGate;

class UserController extends BackendController
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getIndex()
    {

        if ( ! Auth::user()->can('*_users')) {
            abort(403);
        }

        $data[ 'title' ] = 'Users';
        $data[ 'header_title' ] = $data[ 'title' ] . ' List';
        $data[ 'header_description' ] = 'Browse, sort and manage ' . $data[ 'title' ];

        $dataTable = new DataTable('users_list', route('user_dataTables_resultPost'));
        $dataTable->addColumns([
            ['data' => 'id', 'name' => 'id', 'label' => 'ID',],
            ['data' => 'username', 'name' => 'username', 'label' => 'Username',],
            ['data' => 'first_name', 'name' => 'first_name', 'label' => 'First Name',],
            ['data' => 'last_name', 'name' => 'last_name', 'label' => 'Last Name',],
            ['data' => 'email', 'name' => 'email', 'label' => 'E-Mail',],
            ['data' => 'enabled', 'name' => 'enabled', 'label' => 'Enabled',],
            ['data' => 'created_at', 'name' => 'created_at', 'label' => 'Registered On'],
            [
                'data'       => 'actions',
                'name'       => 'actions',
                'label'      => 'Actions',
                'orderable'  => false,
                'searchable' => false,
            ],
        ]);
        $dataTable->setPerPage(20);

        $data[ 'datatable_html' ] = $dataTable->getDataTablesHtml();

        Plugin::add_action('ether_backend_foot', function () use ($dataTable) {
            echo $dataTable->getDataTablesJs('backend::user.datatables-js');
        }, 1501);

        return view('backend::post.list', $data);
    }

    public function postGetUserDataTableResult()
    {
        if ( ! Auth::user()->can('*_users')) {
            abort(403);
        }


        $args = [
            'columns' => ['id', 'username', 'first_name', 'last_name', 'email', 'enabled', 'created_at'],
        ];

        $dataTables = $this->userRepository->dataTable($args);

        $dataTables->editColumn('enabled', function ($value) {
            if (0 == $value->enabled) {
                return 'No';
            }

            return 'Yes';
        });

        $dataTables->addColumn('actions', function ($user) {

            $output = '<div class="datatable-actions">';

            if (Auth::user()->can('edit_users')) {
                $output .= '<a href="' . route('user_edit',
                        $user->id) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Edit</a>';
            }

            if (Auth::user()->can('delete_users')) {
                $output .= '<button class="delete-user-btn btn btn-xs btn-danger" data-user-id="' . $user->id . '"><i class="glyphicon glyphicon-remove"></i> Delete</button>';
            }

            $output .= '</div>';

            return $output;
        });


        return $dataTables->make(true);
    }

    public function postAjaxDeleteUser()
    {
        if (Request::ajax() && Request::has(['user_id'])) {
            $response = [];
            $delete = UserGate::delete(Request::get('user_id'));
            if ($delete != 0) {
                $response[ 'success' ][ 'count' ] = $delete;
            } else {
                $response[ 'error' ][] = 'Nothing deleted';
            }
        } else {
            return response('Forbidden', 403);
        }

        return response($response);
    }

    public function getEdit($userId)
    {
        $user = UserGate::find($userId, [
            'id',
            'first_name',
            'last_name',
            'username',
            'email',
            'created_at',
            'enabled',
        ]);
        if ( ! $user) {
            abort(404);
        }

        $data[ 'title' ] = 'Edit User';
        $data[ 'header_title' ] = 'Users';
        $data[ 'user' ] = $user;

        Plugin::add_action('ether_backend_foot', function () {
            echo '<script type="text/javascript">
                        $(function () {
                            $(\'#user_created_at_date\').datetimepicker(
                                    {
                                        "format": "YYYY-MM-DD HH:mm:ss",
                                    }
                            );
                        });
                    </script>';
        }, 1501);

        return view('backend::user.edit', $data);
    }

    public function putEdit($userId)
    {
        $userData = Request::get('user');

        if ( ! empty($userData)) {
            $userUpdate = UserGate::update($userId, $userData);
            if ($userUpdate instanceof EtherError) {
                return redirect(route('user_edit', $userId))->withErrors($userUpdate);
            }
        }

        return redirect(route('user_edit', $userId))->with('success', 'Information updated successfully');
    }
}