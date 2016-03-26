<?php

namespace Polyether\Backend;

use Asset;
use Auth;
use BootForm;
use Illuminate\Support\Str;
use Plugin;
use Post;
use Taxonomy;

class Backend
{
    protected $menuLinks = [];

    public function onBoot()
    {
        $this->_registerAssetsLoader();
        $this->_registerCoreAssets();
        $this->_registerCoreMenuPages();
        $this->_registerGlobalJs();
    }

    private function _registerAssetsLoader()
    {
        // Enable assets versioning
        Asset::addVersioning();

        Plugin::add_action('ether_backend_head', function () {
            echo Asset::container('backend_header')->show();
        }, 1500);

        Plugin::add_action('ether_backend_foot', function () {
            echo Asset::container('backend_footer')->show();
        }, 1500);
    }

    private function _registerCoreAssets()
    {
        Plugin::add_action('ether_backend_head', function () {
            // Loading default backend assets

            // Header Assets
            Asset::container('backend_header')
                 ->add('backend-bootstrap-css', 'vendor/backend/css/backend-bootstrap.css');
            Asset::container('backend_header')
                 ->add('icheck-square-blue-css', 'vendor/backend/plugins/iCheck/square/blue.css');
            Asset::container('backend_header')->add('select2-css', 'vendor/backend/plugins/select2/select2.min.css');
            Asset::container('backend_header')->add('backend-css', 'vendor/backend/css/backend-adminlte.css');
            Asset::container('backend_header')->add('font-awesome-css',
                    'https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css');
            Asset::container('backend_header')
                 ->add('ionicons-css', 'https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css');
            Asset::container('backend_header')->add('bootstrap-datetimepicker-css',
                    'vendor/backend/plugins/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css');
            Asset::container('backend_header')->add('backend-styles-css', 'vendor/backend/css/backend-styles.css');
        }, 2);

        Plugin::add_action('ether_backend_foot', function () {
            // Footer Assets
            Asset::container('backend_footer')->add('jquery', 'vendor/backend/plugins/jQuery/jQuery-2.1.4.min.js');
            Asset::container('backend_footer')->add('bootstrap-js', 'vendor/backend/js/bootstrap.min.js', 'jquery');
            Asset::container('backend_footer')
                 ->add('icheck-js', 'vendor/backend/plugins/iCheck/icheck.min.js', 'jquery');
            Asset::container('backend_footer')
                 ->add('select2-js', 'vendor/backend/plugins/select2/select2.min.js', 'jquery');
            Asset::container('backend_footer')->add('app-js', 'vendor/backend/js/app.min.js', 'jquery');
            Asset::container('backend_footer')->add('globals-js', 'vendor/backend/js/globals.js', [
                'jquery',
                'app-js',
            ]);
            Asset::container('backend_footer')
                 ->add('moment-js', 'vendor/backend/plugins/moment/moment-with-locales.min.js', 'jquery');
            Asset::container('backend_footer')->add('bootstrap-datetimepicker-js',
                    'vendor/backend/plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js', [
                        'jquery',
                        'moment-js',
                    ]);
        }, 2);
    }

    private function _registerCoreMenuPages()
    {
        Plugin::add_action('init_backend_menu', function () {
            $postTypes = Post::getPostTypes();

            if ( ! empty($postTypes)) {
                foreach ($postTypes as $postType) {
                    if (true == $postType->show_in_admin_menu) {
                        $slug = 'post_type_' . strtolower($postType->name);
                        $title = isset($postType->labels[ 'name' ]) ? $postType->labels[ 'name' ] : $postType->name;
                        $permissions = $postType->permissions;
                        $link = route('post_type_home', $postType->name);
                        $icon = isset($postType->icon) ? $postType->icon : 'fa fa-link';
                        $parent_slug = $this->registerMenuPage($slug, $title, $permissions, $link, $icon,
                            $postType->menu_position);

                        if ($parent_slug) {
                            $singularName = isset($postType->labels[ 'singular' ]) ? $postType->labels[ 'singular' ] : $postType->name;
                            $title = 'Add new ' . $singularName;
                            $link = route('post_create', $postType->name);
                            $icon = 'fa fa-plus';
                            $this->registerMenuSubPage($parent_slug . '_add', $parent_slug, $title, [], $link, $icon);


                            $taxonomies = Taxonomy::getObjectTaxonomies($postType->name, 'objects');

                            if ( ! empty($taxonomies)) {
                                foreach ($taxonomies as $taxonomy) {
                                    if (true == $taxonomy->show_ui && true == $taxonomy->show_in_admin_menu) {
                                        $name = isset($taxonomy->labels[ 'name' ]) ? $taxonomy->labels[ 'name' ] : str_plural(ucfirst($taxonomy->name));
                                        $title = $name;
                                        $link = route('taxonomy_home', $taxonomy->name);
                                        $icon = '';
                                        $this->registerMenuSubPage($parent_slug . '_' . $taxonomy->name, $parent_slug,
                                            $title, [], $link, $icon);
                                    }

                                }
                            }

                        }

                    }
                }
            }
        }, 1);
    }

    private function _registerGlobalJs()
    {
        Plugin::add_action('ether_backend_global_js', function () {
            echo "\n" . 'var ajaxUrl = "' . url('dashboard/ajax') . '";';
        });
    }

    /**
     * @param string       $slug
     * @param string       $title
     * @param array        $permissions
     * @param null|string  $link
     * @param null|string  $icon
     * @param null|integer $position
     *
     * @return bool|string
     */
    public function registerMenuPage($slug, $title, array $permissions, $link = null, $icon = null, $position = null)
    {
        if ($this->menuPageExists($slug)) {
            return false;
        }

        $slug = trim(Str::slug($slug));
        $title = trim($title);
        if ( ! is_array($permissions)) {
            $permissions = (array)$permissions;
        }
        $link = trim($link);
        $position = (null === $position) ? 1500 : (int)$position;


        if ( ! empty($slug) && ! empty($title)) {
            $this->menuLinks[ $slug ] = [
                'title'       => $title,
                'permissions' => $permissions,
                'link'        => $link,
                'icon'        => $icon,
                'position'    => (int)$position,
            ];

            return $slug;
        }

        return false;
    }

    /**
     * @param string      $slug
     * @param string      $parent_slug
     * @param string      $title
     * @param null|array  $permissions
     * @param null|string $link
     * @param null|string $icon
     *
     * @return bool|string
     */
    public function registerMenuSubPage($slug, $parent_slug, $title, $permissions = null, $link = null, $icon = null)
    {
        $slug = trim(Str::slug($slug));
        $title = trim($title);
        if ( ! is_array($permissions)) {
            $permissions = (array)$permissions;
        }
        $link = trim($link);

        if ( ! $this->menuPageExists($parent_slug)) {
            return false;
        }

        if (empty($permissions)) {
            $permissions = $this->getMenuPage($parent_slug)[ 'permissions' ];
        }

        $this->menuLinks[ $parent_slug ][ 'subMenu' ][ $slug ] = [
            'title'       => $title,
            'permissions' => $permissions,
            'link'        => $link,
            'icon'        => $icon,
        ];

        return $slug;
    }

    /**
     * @param string $slug
     *
     * @return bool
     */
    protected function menuPageExists($slug)
    {
        return isset($this->menuLinks[ $slug ]);
    }

    public function getMenuPage($slug)
    {
        return isset($this->menuLinks[ $slug ]) ? $this->menuLinks[ $slug ] : false;
    }

    public function getBackendMenu()
    {
        Plugin::do_action('init_backend_menu');

        uasort($this->menuLinks, function ($a, $b) {
            return $a[ 'position' ] > $b[ 'position' ];
        });

        return $this->generateBackendMenuRecursive($this->menuLinks);
    }

    protected function generateBackendMenuRecursive($menuArray)
    {
        $output = '';
        foreach ($menuArray as $item) {

            if ( ! $this->currentUserCanSeeLink($item[ 'permissions' ])) {
                continue;
            }

            if (isset($item[ 'subMenu' ]) && ! empty($item[ 'subMenu' ])) {
                $icon = ! empty($item[ 'icon' ]) ? "<i class='{$item['icon']}'></i>" : '<i class=\'fa fa-link\'></i>' . "\n";
                $output .= "<li class=\"treeview\">\n";
                $output .= "<a href=\"#\">{$icon}<span>{$item['title']}</span><i class=\"fa fa-angle-left pull-right\"></i></a>\n";
                $output .= "<ul class=\"treeview-menu\">\n";
                $output .= "<li><a href=\"{$item['link']}\">{$icon}<span>{$item['title']}</span></a></li>\n";

                $output .= $this->generateBackendMenuRecursive($item[ 'subMenu' ]);

                $output .= "</ul>\n";
                $output .= "</li>\n";
            } else {
                $icon = ! empty($item[ 'icon' ]) ? "<i class='{$item['icon']}'></i>" : '<i class=\'fa fa-link\'></i>';
                $output .= "<li><a href=\"{$item['link']}\">{$icon}<span>{$item['title']}</span></a></li>\n";
            }
        }

        return $output;

    }

    private function currentUserCanSeeLink($perms)
    {
        if (empty($perms)) {
            return true;
        }

        if (Auth::user()->can($perms)) {
            return true;
        }

        return false;
    }

    public function renderContentEditor($name, $label = null, $value = null, $options = array())
    {

        Plugin::add_action('ether_backend_head', function () {
            //            Asset::container( 'backend_header' )
            //                 ->add( 'froala-editor-css', 'vendor/backend/plugins/froala_editor/css/froala_editor.min.css' );

        }, 3);

        Plugin::add_action('ether_backend_foot', function () {
            Asset::container('backend_footer')
                 ->add('ckeditor-js', asset('vendor/backend/plugins/ckeditor/ckeditor.js'), [
                     'jquery',
                     'globals-js',
                 ])->add('ckeditor-jquery-adapter-js', asset('vendor/backend/plugins/ckeditor/adapters/jquery.js'),
                    ['ckeditor-js'])
                 ->add('post-content-editor-js', asset('vendor/backend/js/content_editor.js'), ['ckeditor-js']);

        }, 3);

        $output = '';

        if (empty($name)) {
            $name = 'post[post_content]';
        }

        $defaultOptions = ['id' => 'post_content', 'class' => 'post-content-editor'];
        $options = array_merge($defaultOptions, $options);

        $output .= BootForm::textarea($name, $label, $value, $options);

        return $output;

    }

    public function processAjax($actionName)
    {
        Plugin::do_action('ajax_backend_' . $actionName);
    }

    protected function sortMenuLinks($a, $b)
    {
        return $a[ 'position' ] > $b[ 'position' ];
    }

}