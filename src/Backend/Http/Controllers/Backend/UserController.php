<?php

namespace Polyether\Backend\Http\Controllers\Backend;

use Auth;
use Illuminate\Http\Request;
use Plugin;
use Polyether\Support\DataTable;
use Polyether\Support\EtherError;
use Post;
use Taxonomy;

class UserController extends BackendController
{

    public function __construct()
    {
    }

    public function getIndex()
    {

        return 'test';

        $postType = Post::getPostTypeObject( $postType );

        if ( $postType ) {
            $perm = '*_' . str_plural( $postType->name );
            if ( ! Auth::user()->can( $perm ) ) {
                abort( 403 );
            }

            $data[ 'title' ] = isset( $postType->labels[ 'name' ] ) ? $postType->labels[ 'name' ] : str_plural( $postType->name );
            $data[ 'header_title' ] = $data[ 'title' ] . ' List';
            $data[ 'header_description' ] = 'Browse, sort and manage all ' . $data[ 'title' ];

            $dataTable = new DataTable( 'post_type_' . $postType->name, route( 'post_type_dataTables_resultPost', $postType->name ) );
            $dataTable->addColumns( [ [ 'data' => 'id', 'name' => 'id', 'label' => 'ID', ],
                                      [ 'data' => 'post_title', 'name' => 'post_title', 'label' => 'Title', ],
                                      [ 'data' => 'post_status', 'name' => 'post_status', 'label' => 'Status', ],
                                      [ 'data' => 'created_at', 'name' => 'created_at', 'label' => 'Created at', ],
                                      [ 'data' => 'updated_at', 'name' => 'updated_at', 'label' => 'Updated at', ],
                                      [ 'data'      => 'actions', 'name' => 'actions', 'label' => 'Actions',
                                        'orderable' => false, 'searchable' => false, ], ] );
            $dataTable->setPerPage( 20 );

            $data[ 'datatable_html' ] = $dataTable->getDataTablesHtml();
            $data[ 'datatable_js' ] = $dataTable->getDataTablesJs();

            return view( 'backend::post.list', $data );

        }

        abort( 404 );

    }

    public function getCreate( $postType )
    {
        if ( Post::postTypeObjectExists( $postType ) ) {
            return 'Welcome to ' . $postType . ' add new page';
        } else {
            abort( 404 );
        }
    }

    public function postCreate( $postType, $data )
    {

    }

    public function getEdit( $postId )
    {
        $post = Post::find( $postId, [ 'id', 'post_author', 'post_content', 'post_title', 'post_excerpt', 'post_status',
                                       'comment_status', 'post_slug', 'post_parent', 'guid', 'menu_order', 'post_type',
                                       'post_mime_type', 'comment_count', 'created_at' ] );
        if ( ! $post ) {
            abort( 404 );
        }


        $postType = Post::getPostTypeObject( $post->post_type );

        $postTaxonomies = Taxonomy::getObjectTaxonomies( $post, 'objects' );

        $uiTaxonomies = Taxonomy::filterTaxonomies( [ 'show_ui' => true ], $postTaxonomies );

        $data[ 'uiTaxonomies' ] = $uiTaxonomies;
        $data[ 'header_title' ] = isset( $postType->labels[ 'name' ] ) ? $postType->labels[ 'name' ] : str_plural( ucfirst( $postType->name ) );
        $data[ 'title' ] = 'Edit ' . ucfirst( $post->post_type );
        $data[ 'post' ] = $post;

        Plugin::add_action( 'ether_backend_foot', function() {
            echo '<script type="text/javascript">
                        $(function () {
                            $(\'#post_created_at_date\').datetimepicker(
                                    {
                                        "format": "YYYY-MM-DD HH:mm:ss"
                                    }
                            );
                        });
                    </script>';
        }, 1 );

        return view( 'backend::post.edit', $data );
    }

    public function putEdit( $postId, Request $request )
    {
        $postData = $request->except( [ '_method', '_token', 'taxonomy' ] );
        $taxonomies = $request->get( 'taxonomy' );


        if ( ! empty( $postData ) ) {
            $postUpdate = Post::update( $postId, $postData );
            if ( $postUpdate instanceof EtherError ) {
                return redirect( route( 'post_edit', $postId ) )->withErrors( $postUpdate );
            }
        }

        if ( isset( $taxonomies ) ) {
            foreach ( $taxonomies as $taxonomy => $terms ) {
                $terms = array_map( 'intval', $terms );
                Taxonomy::setObjectTerms( $postId, $terms, $taxonomy );
            }
        }

        return redirect( route( 'post_edit', $postId ) )->with( 'success', 'Information updated successfully' );
    }

    public function postGetPostTypeDataTableResult( $postType )
    {

        $postType = Post::getPostTypeObject( $postType );

        if ( ! $postType ) {
            abort( 400 );
        }

        if ( ! Auth::user()->can( '*_' . str_plural( $postType->name ) ) ) {
            abort( 403 );
        }


        $args = [ 'columns' => [ 'id', 'post_title', 'post_status', 'created_at', 'updated_at' ],
                  'query'   => [ [ 'column' => 'post_type', 'value' => e( $postType->name ), ], ], ];

        $dataTables = $this->postRepository->dataTable( $args );

        $dataTables->addColumn( 'actions', function( $post ) use ( $postType ) {

            $output = '';

            if ( Auth::user()->can( 'edit_' . str_plural( $postType->name ) ) ) {
                $output .= '<a href="' . route( 'post_edit', $post->id ) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Edit</a>';
            }

            return $output;
        } );


        return $dataTables->make( true );
    }

    private function _taxonomyBodyGenerator( $postType )
    {

    }
}