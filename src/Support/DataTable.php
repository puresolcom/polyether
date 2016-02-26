<?php

namespace Polyether\Support;

use Asset;
use View;

class DataTable
{

    protected $columns;
    protected $tableSelector;
    protected $ajaxUrl;
    protected $perPage = 20;

    public function __construct ( $tableSelector, $ajaxUrl )
    {
        $this->tableSelector = $tableSelector;
        $this->ajaxUrl = $ajaxUrl;

        $this->appendAssets();
    }

    private function appendAssets ()
    {
        Asset::container( 'backend_footer' )->add( 'dataTables-bootstrap-css', 'vendor/backend/plugins/datatables/dataTables.bootstrap.css', 'bootstrap-css' );
        Asset::container( 'backend_footer' )->add( 'dataTables-js', 'vendor/backend/plugins/datatables/jquery.dataTables.min.js', 'jquery' );
        Asset::container( 'backend_footer' )->add( 'dataTables-bootstrap-js', 'vendor/backend/plugins/datatables/dataTables.bootstrap.min.js', 'jquery' );
    }

    public function setPerPage ( $perPage )
    {
        if ( ! is_numeric( $perPage ) )
            return;

        $this->perPage = (int)$perPage;
    }

    public function addColumns ( array $columns )
    {
        foreach ( $columns as $column ) {
            $this->addColumn( $column );
        }
    }

    public function addColumn ( array $column )
    {
        if ( ! isset( $column[ 'data' ], $column[ 'name' ], $column[ 'label' ] ) )
            return false;

        $this->columns[] = (object)$column;
    }

    public function getDataTablesHtml ()
    {
        $data[ 'tableSelector' ] = $this->tableSelector;
        $data[ 'columns' ] = $this->getColumns();

        return View::make( 'backend::support.datatables.datatable-html', $data )->render();
    }

    public function getColumns ()
    {
        return $this->columns;
    }

    public function getDataTablesJs ()
    {
        $data[ 'columns' ] = json_encode( $this->getColumns() );
        $data[ 'ajaxUrl' ] = $this->ajaxUrl;
        $data[ 'tableSelector' ] = $this->tableSelector;
        $data [ 'perPage' ] = $this->perPage;

        return View::make( 'backend::support.datatables.datatable-js', $data )->render();
    }

}