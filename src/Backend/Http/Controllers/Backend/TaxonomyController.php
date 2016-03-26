<?php


namespace Polyether\Backend\Http\Controllers\Backend;

use Auth;
use Plugin;
use Polyether\Backend\Http\Controllers\Backend\BackendController as Controller;
use Polyether\Support\DataTable;
use Polyether\Support\EtherError;
use Polyether\Taxonomy\Repositories\TermRepository;
use Polyether\Taxonomy\Repositories\TermTaxonomyRepository;
use Request;
use Taxonomy;

class TaxonomyController extends Controller
{

    protected $termTaxonomyRepository;
    protected $termRepository;

    public function __construct(TermTaxonomyRepository $termTaxonomyRepository, TermRepository $termRepository)
    {
        $this->termTaxonomyRepository = $termTaxonomyRepository;
        $this->termRepository = $termRepository;
    }

    public function getIndex($taxName)
    {
        if ( ! Taxonomy::taxonomyExists($taxName)) {
            abort(404);
        }

        $taxonomy = Taxonomy::getTaxonomy($taxName);

        $data[ 'taxonomy' ] = $taxonomy;
        $data[ 'title' ] = isset($taxonomy->labels[ 'name' ]) ? $taxonomy->labels[ 'name' ] : ucfirst(str_plural($taxName));
        $data[ 'header_title' ] = $data[ 'title' ];

        $dataTable = new DataTable('taxonomy_terms_' . $taxonomy->name,
            route('taxonomy_datatables_resultPost', $taxonomy->name));
        $dataTable->addColumns([
            ['data' => 'name', 'name' => 'name', 'label' => 'Name',],
            ['data' => 'description', 'name' => 'description', 'label' => 'Description',],
            ['data' => 'slug', 'name' => 'slug', 'label' => 'Slug',],
            ['data' => 'count', 'name' => 'count', 'label' => 'Count',],
            [
                'data'       => 'actions',
                'name'       => 'actions',
                'label'      => 'Actions',
                'orderable'  => false,
                'searchable' => false,
            ],
        ]);
        $dataTable->setPerPage(10);

        $data[ 'datatable_html' ] = $dataTable->getDataTablesHtml();

        Plugin::add_action('ether_backend_foot', function () use ($dataTable, $taxonomy) {
            echo $dataTable->getDataTablesJs('backend::taxonomy.datatables-js');
        }, 1501);


        return view('backend::taxonomy.index', $data);
    }

    public function postGetTaxonomyDataTableResult($taxonomy)
    {

        $taxonomy = Taxonomy::getTaxonomy($taxonomy);

        if ( ! $taxonomy) {
            abort(400);
        }

        if ( ! Auth::user()->can('*_terms')) {
            abort(403);
        }


        $args = [
            'columns' => ['terms.id', 'name', 'description', 'slug', 'count', 'parent'],
            'query'   => [['column' => 'taxonomy', 'value' => e($taxonomy->name),],],
        ];

        $dataTables = $this->termTaxonomyRepository->dataTable($args);

        $dataTables->addColumn('actions', function ($term) use ($taxonomy) {

            $output = '<div class="datatable-actions">';

            if (Auth::user()->can('edit_terms')) {
                $output .= '<a href="' . route('term_taxonomy_edit', [
                        $taxonomy->name,
                        $term->id,
                    ]) . '" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Edit</a>';
            }

            if (Auth::user()->can('delete_terms')) {
                $output .= '<button class="delete-term-btn btn btn-xs btn-danger" data-term-id="' . $term->id . '" data-taxonomy="' . $taxonomy->name . '"><i class="glyphicon glyphicon-remove"></i> Delete</button>';
            }

            $output .= '</div>';

            return $output;
        });

        return $dataTables->make(true);
    }

    public function postAjaxDeleteTerm()
    {
        if (Request::ajax() && Request::has(['term_id', 'taxonomy'])) {
            $response = [];
            $delete = Taxonomy::deleteTerm((int)Request::get('term_id'), Request::get('taxonomy'));
            if ($delete != 0) {
                $taxonomy = Taxonomy::getTaxonomy(Request::get('taxonomy'));
                $response[ 'success' ][ 'count' ] = $delete;
                if ($taxonomy->hierarchical) {
                    $response[ 'success' ][ 'replaces' ][ Request::get('taxonomy') . '_parent' ] = view('backend::taxonomy.taxonomy-parent-select',
                        [
                            'exclude' => [(int)Request::get('term_id')],
                            'taxName' => Request::get('taxonomy'),
                        ])->render();
                }
            } else {
                $response[ 'error' ][] = 'Nothing deleted';
            }
        } else {
            return response('Forbidden', 403);
        }

        return response($response);
    }

    public function postAddTerm($taxonomy)
    {

        $term = Request::all();

        if (empty($term[ 'name' ])) {
            abort(400);
        }

        if ( ! empty($term[ 'slug' ])) {
            $args[ 'slug' ] = $term[ 'slug' ];
        }

        if (is_numeric($term[ 'parent' ]) && 0 != $term[ 'parent' ]) {
            $args[ 'parent' ] = (int)$term[ 'parent' ];
        }

        if (isset($term[ 'description' ])) {
            $args[ 'description' ] = $term[ 'description' ];
        }

        $createdTerm = Taxonomy::createTerm($term[ 'name' ], $taxonomy, $args);

        if ($createdTerm && ! $createdTerm instanceOf EtherError) {
            return redirect()->route('taxonomy_home', $taxonomy)->with('success', 'Term added successfully');
        } else {
            return redirect()->route('taxonomy_home', $taxonomy)->withErrors($createdTerm);
        }
    }

    public function getEditTerm($taxonomyName, $termId)
    {

        if ( ! Taxonomy::taxonomyExists($taxonomyName)) {
            abort(404);
        }

        $taxonomy = Taxonomy::getTaxonomy($taxonomyName);

        $term = Taxonomy::getTerm((int)$termId, $taxonomyName);

        if ( ! $term) {
            abort(404);
        }

        if ( ! Auth::user()->can('edit_terms')) {
            abort(403);
        }

        $data[ 'term' ] = $term;
        $data[ 'taxonomy' ] = $taxonomy;

        $taxonomyLabel = isset($taxonomy->labels[ 'singular' ]) ? $taxonomy->labels[ 'singular' ] : ucfirst($taxonomy->name);

        $data[ 'title' ] = 'Edit ' . $taxonomyLabel;
        $data[ 'header_title' ] = $data[ 'title' ];


        return view('backend::taxonomy.edit', $data);
    }

    public function putEditTerm($taxonomyName, $termId)
    {

        if ( ! Taxonomy::taxonomyExists($taxonomyName)) {
            abort(404);
        }

        $term = Taxonomy::termExists((int)$termId, $taxonomyName);

        if ( ! $term) {
            abort(404);
        }

        if ( ! Auth::user()->can('edit_terms')) {
            abort(403);
        }

        $updatedData = Request::except(['_method', '_token']);

        $updateTerm = Taxonomy::updateTerm($termId, $taxonomyName, $updatedData);
        if ($updateTerm instanceof EtherError) {
            return redirect(route('term_taxonomy_edit', [
                $taxonomyName,
                $termId,
            ]))->with('error', $updateTerm->all());
        }

        $redirectUrl = route('taxonomy_home', $taxonomyName);

        return redirect(route('term_taxonomy_edit', [
            $taxonomyName,
            $termId,
        ]))->with('success', [
            '<b>Done</b>, Term updated successfully <br /><br />',
            '<a href="' . $redirectUrl . '">&larr; Go back</a>',
        ]);
    }
}