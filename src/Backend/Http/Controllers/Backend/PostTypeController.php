<?php

namespace Polyether\Backend\Http\Controllers\Backend;

use Asset;
use Auth;
use Plugin;
use Polyether\Post\Repositories\PostRepository;
use Polyether\Support\DataTable;
use Polyether\Support\EtherError;
use Post;
use Request;
use Taxonomy;

class PostTypeController extends BackendController
{

    protected $postRepository;

    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function getIndex($postType)
    {

        $postType = Post::getPostTypeObject($postType);

        if ($postType) {
            $perm = ! empty($postType->permissions[ 'view_posts' ]) ? $postType->permissions[ 'view_posts' ] : '';
            if ( ! Auth::user()->can($perm)) {
                abort(403);
            }

            $data[ 'title' ] = isset($postType->labels[ 'name' ]) ? $postType->labels[ 'name' ] : str_plural($postType->name);
            $data[ 'header_title' ] = $data[ 'title' ] . ' List';
            $data[ 'header_description' ] = 'Browse, sort and manage all ' . $data[ 'title' ];

            $dataTable = new DataTable('post_type_' . $postType->name,
                route('post_type_dataTables_resultPost', $postType->name));
            $dataTable->addColumns([
                ['data' => 'id', 'name' => 'id', 'label' => 'ID',],
                ['data' => 'post_title', 'name' => 'post_title', 'label' => 'Title',],
                ['data' => 'post_status', 'name' => 'post_status', 'label' => 'Status',],
                ['data' => 'created_at', 'name' => 'created_at', 'label' => 'Created at',],
                ['data' => 'updated_at', 'name' => 'updated_at', 'label' => 'Updated at',],
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
                echo $dataTable->getDataTablesJs('backend::post.datatables-js');
            }, 1501);

            return view('backend::post.list', $data);
        }
        abort(404);
    }

    public function getEdit($postId)
    {
        $post = Post::find($postId, [
            'id',
            'post_author',
            'post_content',
            'post_title',
            'post_excerpt',
            'post_status',
            'comment_status',
            'post_slug',
            'post_parent',
            'guid',
            'menu_order',
            'post_type',
            'post_mime_type',
            'comment_count',
            'created_at',
        ]);
        if ( ! $post) {
            abort(404);
        }

        Plugin::add_action('ether_backend_global_js', function () use ($postId) {
            echo "\n" . 'var objectId = ' . $postId . ';';
        });

        Plugin::add_action('ether_backend_foot', function () {
            Asset::container('backend_footer')->add('edit-post-js', 'vendor/backend/js/edit_post.js', ['jquery']);
        });


        $postType = Post::getPostTypeObject($post->post_type);

        $postTaxonomies = Taxonomy::getObjectTaxonomies($post, 'objects');

        $uiTaxonomies = Taxonomy::filterTaxonomies(['show_ui' => true], 'objects', 'and', $postTaxonomies);

        $data[ 'uiTaxonomies' ] = $uiTaxonomies;
        $data[ 'title' ] = 'Edit ' . ucfirst($post->post_type);
        $data[ 'header_title' ] = isset($postType->labels[ 'name' ]) ? $postType->labels[ 'name' ] : str_plural(ucfirst($postType->name));
        $data[ 'post' ] = $post;
        $data[ 'postId' ] = $post->id;

        Plugin::add_action('ether_backend_foot', function () {

            echo '<script type="text/javascript">
                        $(function () {
                            $(\'#post_created_at_date\').datetimepicker(
                                    {
                                        "format": "YYYY-MM-DD HH:mm:ss",
                                    }
                            );
                        });
                    </script>';
        }, 1501);

        return view('backend::post.edit', $data);
    }

    public function putEdit($postId)
    {
        $postData = Request::get('post');
        $taxonomies = Request::get('taxonomy');

        if ( ! empty($postData)) {
            $postUpdate = Post::update($postId, $postData);
            if ($postUpdate instanceof EtherError) {
                return redirect(route('post_edit', $postId))->withErrors($postUpdate);
            }
        }

        if (isset($taxonomies)) {
            foreach ($taxonomies as $taxonomy => $terms) {
                $terms = array_map(function ($term) {
                    return is_numeric($term) ? (int)$term : $term;
                }, $terms);
                Taxonomy::setObjectTerms($postId, $terms, $taxonomy);
            }
        }

        return redirect(route('post_edit', $postId))->with('success', 'Information updated successfully');
    }

    public function getCreate($postType)
    {
        if ( ! Post::postTypeObjectExists($postType)) {
            abort(404);
        }

        $postTypeObject = Post::getPostTypeObject($postType);

        Plugin::add_action('ether_backend_foot', function () {
            Asset::container('backend_footer')->add('ajax-edit-post-js', 'vendor/backend/js/edit_post.js', ['jquery']);
        });

        $postTaxonomies = Taxonomy::getObjectTaxonomies($postType, 'objects');
        $uiTaxonomies = Taxonomy::filterTaxonomies(['show_ui' => true], 'objects', 'and', $postTaxonomies);

        $data[ 'postTypeObject' ] = $postTypeObject;
        $data[ 'uiTaxonomies' ] = $uiTaxonomies;
        $data[ 'header_title' ] = isset($postTypeObject->labels[ 'name' ]) ? $postTypeObject->labels[ 'name' ] : str_plural(ucfirst($postTypeObject->name));
        $data[ 'title' ] = 'Edit ' . ucfirst($postTypeObject->name);

        Plugin::add_action('ether_backend_foot', function () {
            echo '<script type="text/javascript">
                        $(function () {
                            $(\'#post_created_at_date\').datetimepicker(
                                    {
                                        "format": "YYYY-MM-DD HH:mm:ss"
                                    }
                            );
                        });
                    </script>';
        }, 1501);

        return view('backend::post.create', $data);
    }

    public function postCreate($postType)
    {

        $postData = Request::get('post');
        $taxonomies = Request::get('taxonomy');

        $postData[ 'post_type' ] = Request::segment(3);

        if ( ! empty($postData)) {
            $post = Post::create($postData);
            if ($post instanceof EtherError) {
                return redirect(route('post_create', $postType))->withInput()->withErrors($post);
            }

            $postId = (int)$post->id;
        }

        if (isset($taxonomies)) {
            foreach ($taxonomies as $taxonomy => $terms) {
                $terms = array_map(function ($term) {
                    return is_numeric($term) ? (int)$term : $term;
                }, $terms);
                Taxonomy::setObjectTerms($postId, $terms, $taxonomy);
            }
        }

        return redirect(route('post_type_home', $post->post_type))->with('success', 'Post Created successfully');
    }

    public function postAjaxDeletePost()
    {
        if (Request::ajax() && Request::has(['post_id'])) {
            $response = [];
            $delete = Post::delete(Request::get('post_id'));
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

    public function postGetPostTypeDataTableResult($postType)
    {

        $postType = Post::getPostTypeObject($postType);

        if ( ! $postType) {
            abort(400);
        }

        $perm = ! empty($postType->permissions[ 'view_posts' ]) ? $postType->permissions[ 'view_posts' ] : '';

        if ( ! Auth::user()->can($perm)) {
            abort(403);
        }


        $args = [
            'columns' => ['id', 'post_title', 'post_status', 'created_at', 'updated_at'],
            'query'   => [['column' => 'post_type', 'value' => e($postType->name),],],
        ];

        if ( ! Auth::user()->hasRole('administrator')) {
            $args[ 'query' ][] = ['column' => 'post_author', 'value' => Auth::user()->id];
        }

        $dataTables = $this->postRepository->dataTable($args);

        $dataTables->addColumn('actions', function ($post) use ($postType) {

            $edit_perm = ! empty($postType->permissions[ 'edit_posts' ]) ? $postType->permissions[ 'edit_posts' ] : '';
            $delete_perm = ! empty($postType->permissions[ 'delete_posts' ]) ? $postType->permissions[ 'delete_posts' ] : '';

            $output = '<div class="datatable-actions">';

            if (Auth::user()->can($edit_perm)) {
                $output .= '<a href="' . route('post_edit',
                        $post->id) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Edit</a>';
            }

            if (Auth::user()->can($delete_perm)) {
                $output .= '<button class="delete-term-btn btn btn-xs btn-danger" data-post-id="' . $post->id . '"><i class="glyphicon glyphicon-remove"></i> Delete</button>';
            }

            $output .= '</div>';

            return $output;
        });


        return $dataTables->make(true);
    }
}