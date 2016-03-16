<?php

namespace Polyether\Plugin;

use Option;

/**
 * Plugins API
 *
 * @author Mohammed Anwar <m.anwar@pure-sol.com>
 */
class PluginAPI
{

    protected $filter = [ ], $actions = [ ], $merged_filters = [ ], $current_filter = [ ];
    private $pluginsDir;

    public function __construct()
    {
        $this->pluginsDir = base_path( 'plugins' );
    }

    public function init()
    {
        $plugins = $this->resolvePlugins();
        $this->includePlugins( $plugins );
    }

    private function resolvePlugins()
    {

        $plugins_dirs = array_diff( scandir( $this->getPluginsDir(), SCANDIR_SORT_ASCENDING ), array( '.', '..' ) );

        if ( false !== $plugins_dirs ) {
            $lastModifiedDate = filemtime( $this->getPluginsDir() );

            if ( $lastModifiedDate == Option::get( 'plugins_dir_lmd' ) ) {
                if ( false !== Option::get( 'plugins_cached_dir' ) ) {
                    return Option::get( 'plugins_cached_dir' );
                }
            }

            Option::update( 'plugins_dir_lmd', $lastModifiedDate, true );
            Option::update( 'plugins_cached_dir', $plugins_dirs, true );

            return $plugins_dirs;
        }

        return [ ];
    }

    private function includePlugins( $plugins )
    {

        if ( ! is_array( $plugins ) || ! is_object( $plugins ) ) {
            return false;
        }

        $plugins = (array)$plugins;

        if ( count( $plugins ) > 0 ) {
            foreach ( $plugins as $plugin_dir_name ) {

                $plugin_file_name = $this->getPluginsDir() . DIRECTORY_SEPARATOR . $plugin_dir_name . DIRECTORY_SEPARATOR . $plugin_dir_name . '.php';

                if ( file_exists( $plugin_file_name ) ) {
                    require $plugin_file_name;
                }
            }
        }
    }

    public function getPluginsDir()
    {
        return $this->pluginsDir;
    }

    public function setPluginsDir( $dir )
    {
        $this->pluginsDir = $dir;
    }

    /**
     * Call the functions added to a filter hook.
     *
     * @param string $tag   The name of the filter hook.
     * @param mixed  $value The value on which the filters hooked to `$tag` are applied on.
     * @param mixed  $var   Additional variables passed to the functions hooked to `$tag`.
     *
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function apply_filters( $tag, $value )
    {
        $args = array();

        // Do 'all' actions first.
        if ( isset( $this->filter[ 'all' ] ) ) {
            $this->current_filter[] = $tag;
            $args = func_get_args();
            self::_call_all_hook( $args );
        }

        if ( ! isset( $this->filter[ $tag ] ) ) {
            if ( isset( $this->filter[ 'all' ] ) ) {
                array_pop( $this->current_filter );
            }

            return $value;
        }

        if ( ! isset( $this->filter[ 'all' ] ) ) {
            $this->current_filter[] = $tag;
        }

        // Sort.
        if ( ! isset( $this->merged_filters[ $tag ] ) ) {
            ksort( $this->filter[ $tag ] );
            $this->merged_filters[ $tag ] = true;
        }

        reset( $this->filter[ $tag ] );

        if ( empty( $args ) ) {
            $args = func_get_args();
        }
        do {
            foreach ( (array)current( $this->filter[ $tag ] ) as $the_ )
                if ( ! is_null( $the_[ 'function' ] ) ) {
                    $args[ 1 ] = $value;
                    $value = call_user_func_array( $the_[ 'function' ], array_slice( $args, 1, (int)$the_[ 'accepted_args' ] ) );
                }
        } while ( next( $this->filter[ $tag ] ) !== false );

        array_pop( $this->current_filter );

        return $value;
    }

    /**
     * Call the 'all' hook, which will process the functions hooked into it.
     *
     * The 'all' hook passes all of the arguments or parameters that were used for
     * the hook, which this function was called for.
     *
     * This function is used internally for apply_filters(), do_action(), and
     * do_action_ref_array() and is not meant to be used from outside those
     * functions. This function does not check for the existence of the all hook, so
     * it will fail unless the all hook exists prior to this function call.
     *
     * @global array $wp_filter Stores all of the filters
     *
     * @param array  $args      The collected parameters from the hook that was called.
     */
    private function _call_all_hook( $args )
    {
        reset( $this->filter[ 'all' ] );
        do {
            foreach ( (array)current( $this->filter[ 'all' ] ) as $the_ )
                if ( ! is_null( $the_[ 'function' ] ) ) {
                    call_user_func_array( $the_[ 'function' ], $args );
                }
        } while ( next( $this->filter[ 'all' ] ) !== false );
    }

    /**
     * Execute functions hooked on a specific filter hook, specifying arguments in an array.
     *
     * @see apply_filters() This function is identical, but the arguments passed to the
     * functions hooked to `$tag` are supplied using an array.
     *
     * @param string $tag  The name of the filter hook.
     * @param array  $args The arguments supplied to the functions hooked to $tag.
     *
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    function apply_filters_ref_array( $tag, $args )
    {
        // Do 'all' actions first
        if ( isset( $this->filter[ 'all' ] ) ) {
            $this->current_filter[] = $tag;
            $all_args = func_get_args();
            $this->_call_all_hook( $all_args );
        }

        if ( ! isset( $this->filter[ $tag ] ) ) {
            if ( isset( $this->filter[ 'all' ] ) ) {
                array_pop( $this->current_filter );
            }

            return $args[ 0 ];
        }

        if ( ! isset( $this->filter[ 'all' ] ) ) {
            $this->current_filter[] = $tag;
        }

        // Sort
        if ( ! isset( $this->merged_filters[ $tag ] ) ) {
            ksort( $this->filter[ $tag ] );
            $this->merged_filters[ $tag ] = true;
        }

        reset( $this->filter[ $tag ] );

        do {
            foreach ( (array)current( $this->filter[ $tag ] ) as $the_ )
                if ( ! is_null( $the_[ 'function' ] ) ) {
                    $args[ 0 ] = call_user_func_array( $the_[ 'function' ], array_slice( $args, 0, (int)$the_[ 'accepted_args' ] ) );
                }
        } while ( next( $this->filter[ $tag ] ) !== false );

        array_pop( $this->current_filter );

        return $args[ 0 ];
    }

    /**
     * Retrieve the name of the current action.
     *
     * @return string Hook name of the current action.
     */
    public function current_action()
    {
        return self::current_filter();
    }

    /**
     * Retrieve the name of the current filter or action.
     *
     * @return string Hook name of the current filter or action.
     */
    function current_filter()
    {
        return end( $this->current_filter );
    }

    /**
     * Retrieve the name of an action currently being processed.
     *
     * @param string|null $action Optional. Action to check. Defaults to null, which checks
     *                            if any action is currently being run.
     *
     * @return bool Whether the action is currently in the stack.
     */
    public function doing_action( $action = null )
    {
        return self::doing_filter( $action );
    }

    /**
     * Retrieve the name of a filter currently being processed.
     *
     * The function current_filter() only returns the most recent filter or action
     * being executed. did_action() returns true once the action is initially
     * processed.
     *
     * This function allows detection for any filter currently being
     * executed (despite not being the most recent filter to fire, in the case of
     * hooks called from hook callbacks) to be verified.
     *
     * @param null|string $filter Optional. Filter to check. Defaults to null, which
     *                            checks if any filter is currently being run.
     *
     * @return bool Whether the filter is currently in the stack.
     */
    public function doing_filter( $filter = null )
    {

        if ( null === $filter ) {
            return ! empty( $this->current_filter );
        }

        return in_array( $filter, $this->current_filter );
    }

    /**
     * Hooks a function on to a specific action.
     *
     * Actions are the hooks that the Ether core launches at specific points
     * during execution, or when specific events occur. Plugins can specify that
     * one or more of its PHP functions are executed at these points, using the
     * Action API.
     *
     * @param string   $tag             The name of the action to which the $function_to_add is hooked.
     * @param callback $function_to_add The name of the function you wish to be called.
     * @param int      $priority        Optional. Used to specify the order in which the functions
     *                                  associated with a particular action are executed. Default 10.
     *                                  Lower numbers correspond with earlier execution,
     *                                  and functions with the same priority are executed
     *                                  in the order in which they were added to the action.
     * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
     *
     * @return true Will always return true.
     */
    public function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 )
    {
        return self::add_filter( $tag, $function_to_add, $priority, $accepted_args );
    }

    /**
     * Hook a function or method to a specific filter action.
     *
     * @param string   $tag             The name of the filter to hook the $function_to_add callback to.
     * @param callback $function_to_add The callback to be run when the filter is applied.
     * @param int      $priority        Optional. Used to specify the order in which the functions
     *                                  associated with a particular action are executed. Default 10.
     *                                  Lower numbers correspond with earlier execution,
     *                                  and functions with the same priority are executed
     *                                  in the order in which they were added to the action.
     * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
     *
     * @return true
     */
    public function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 )
    {
        $idx = self::_filter_build_unique_id( $tag, $function_to_add, $priority );
        $this->filter[ $tag ][ $priority ][ $idx ] = array( 'function'      => $function_to_add,
                                                            'accepted_args' => $accepted_args );
        unset( $this->merged_filters[ $tag ] );

        return true;
    }

    /**
     * Build Unique ID for storage and retrieval.
     *
     * @param string   $tag      Used in counting how many hooks were applied
     * @param callback $function Used for creating unique id
     * @param int|bool $priority Used in counting how many hooks were applied. If === false
     *                           and $function is an object reference, we return the unique
     *                           id only if it already has one, false otherwise.
     *
     * @return string|false Unique ID for usage as array key or false if $priority === false
     *                      and $function is an object reference, and it does not already have
     *                      a unique id.
     */
    private function _filter_build_unique_id( $tag, $function, $priority )
    {

        static $filter_id_count = 0;

        if ( is_string( $function ) ) {
            return $function;
        }

        if ( is_object( $function ) ) {
            // Closures are currently implemented as objects
            $function = array( $function, '' );
        } else {
            $function = (array)$function;
        }

        if ( is_object( $function[ 0 ] ) ) {
            // Object Class Calling
            if ( function_exists( 'spl_object_hash' ) ) {
                return spl_object_hash( $function[ 0 ] ) . $function[ 1 ];
            } else {
                $obj_idx = get_class( $function[ 0 ] ) . $function[ 1 ];
                if ( ! isset( $function[ 0 ]->filter_id ) ) {
                    if ( false === $priority ) {
                        return false;
                    }
                    $obj_idx .= isset( $this->filter[ $tag ][ $priority ] ) ? count( (array)$this->filter[ $tag ][ $priority ] ) : $filter_id_count;
                    $function[ 0 ]->filter_id = $filter_id_count;
                    ++$filter_id_count;
                } else {
                    $obj_idx .= $function[ 0 ]->filter_id;
                }

                return $obj_idx;
            }
        } elseif ( is_string( $function[ 0 ] ) ) {
            // Static Calling
            return $function[ 0 ] . '::' . $function[ 1 ];
        }
    }

    /**
     * Execute functions hooked on a specific action hook.
     *
     * This function invokes all functions attached to action hook `$tag`. It is
     * possible to create new action hooks by simply calling this function,
     * specifying the name of the new hook using the `$tag` parameter.
     *
     * You can pass extra arguments to the hooks, much like you can with
     * {@see apply_filters()}.
     *
     * @param string $tag The name of the action to be executed.
     * @param mixed  $arg Optional. Additional arguments which are passed on to the
     *                    functions hooked to the action. Default empty.
     */
    public function do_action( $tag, $arg = '' )
    {
        if ( ! isset( $this->actions[ $tag ] ) ) {
            $this->actions[ $tag ] = 1;
        } else {
            ++$this->actions[ $tag ];
        }

        // Do 'all' actions first
        if ( isset( $this->filter[ 'all' ] ) ) {
            $this->current_filter[] = $tag;
            $all_args = func_get_args();
            self::_call_all_hook( $all_args );
        }

        if ( ! isset( $this->filter[ $tag ] ) ) {
            if ( isset( $this->filter[ 'all' ] ) ) {
                array_pop( $this->current_filter );
            }

            return;
        }

        if ( ! isset( $this->filter[ 'all' ] ) ) {
            $this->current_filter[] = $tag;
        }

        $args = array();
        if ( is_array( $arg ) && 1 == count( $arg ) && isset( $arg[ 0 ] ) && is_object( $arg[ 0 ] ) ) // array(&$this)
        {
            $args[] = &$arg[ 0 ];
        } else {
            $args[] = $arg;
        }
        for ( $a = 2, $num = func_num_args(); $a < $num; $a++ )
            $args[] = func_get_arg( $a );

        // Sort
        if ( ! isset( $this->merged_filters[ $tag ] ) ) {
            ksort( $this->filter[ $tag ] );
            $this->merged_filters[ $tag ] = true;
        }

        reset( $this->filter[ $tag ] );

        do {
            foreach ( (array)current( $this->filter[ $tag ] ) as $the_ )
                if ( ! is_null( $the_[ 'function' ] ) ) {
                    call_user_func_array( $the_[ 'function' ], array_slice( $args, 0, (int)$the_[ 'accepted_args' ] ) );
                }
        } while ( next( $this->filter[ $tag ] ) !== false );

        array_pop( $this->current_filter );
    }

    /**
     * Retrieve the number of times an action is fired.
     *
     * @param string $tag The name of the action hook.
     *
     * @return int The number of times action hook $tag is fired.
     */
    public function did_action( $tag )
    {
        if ( ! isset( $this->actions[ $tag ] ) ) {
            return 0;
        }

        return $this->actions[ $tag ];
    }

    /**
     * Execute functions hooked on a specific action hook, specifying arguments in an array.
     *
     * @see do_action() This function is identical, but the arguments passed to the
     *
     * @param string $tag  The name of the action to be executed.
     * @param array  $args The arguments supplied to the functions hooked to `$tag`.
     */
    public function do_action_ref_array( $tag, $args )
    {
        if ( ! isset( $this->actions[ $tag ] ) ) {
            $this->actions[ $tag ] = 1;
        } else {
            ++$this->actions[ $tag ];
        }

        // Do 'all' actions first
        if ( isset( $this->filter[ 'all' ] ) ) {
            $this->current_filter[] = $tag;
            $all_args = func_get_args();
            self::_call_all_hook( $all_args );
        }

        if ( ! isset( $this->filter[ $tag ] ) ) {
            if ( isset( $this->filter[ 'all' ] ) ) {
                array_pop( $this->current_filter );
            }

            return;
        }

        if ( ! isset( $this->filter[ 'all' ] ) ) {
            $this->current_filter[] = $tag;
        }

        // Sort
        if ( ! isset( $this->merged_filters[ $tag ] ) ) {
            ksort( $this->filter[ $tag ] );
            $this->merged_filters[ $tag ] = true;
        }

        reset( $this->filter[ $tag ] );

        do {
            foreach ( (array)current( $this->filter[ $tag ] ) as $the_ )
                if ( ! is_null( $the_[ 'function' ] ) ) {
                    call_user_func_array( $the_[ 'function' ], array_slice( $args, 0, (int)$the_[ 'accepted_args' ] ) );
                }
        } while ( next( $this->filter[ $tag ] ) !== false );

        array_pop( $this->current_filter );
    }

    /**
     * Check if any action has been registered for a hook.
     *
     * @see has_filter() has_action() is an alias of has_filter().
     *
     * @param string        $tag               The name of the action hook.
     * @param callback|bool $function_to_check Optional. The callback to check for. Default false.
     *
     * @return bool|int If $function_to_check is omitted, returns boolean for whether the hook has
     *                  anything registered. When checking a specific function, the priority of that
     *                  hook is returned, or false if the function is not attached. When using the
     *                  $function_to_check argument, this function may return a non-boolean value
     *                  that evaluates to false (e.g.) 0, so use the === operator for testing the
     *                  return value.
     */
    public function has_action( $tag, $function_to_check = false )
    {
        return self::has_filter( $tag, $function_to_check );
    }

    /**
     *
     * Check if any filter has been registered for a hook.
     *
     * @param string        $tag               The name of the filter hook.
     * @param callback|bool $function_to_check Optional. The callback to check for. Default false.
     *
     * @return false|int If $function_to_check is omitted, returns boolean for whether the hook has
     *                   anything registered. When checking a specific function, the priority of that
     *                   hook is returned, or false if the function is not attached. When using the
     *                   $function_to_check argument, this function may return a non-boolean value
     *                   that evaluates to false (e.g.) 0, so use the === operator for testing the
     *                   return value.
     */
    public function has_filter( $tag, $function_to_check = false )
    {
        // Don't reset the internal array pointer
        $filter = $this->filter;

        $has = ! empty( $filter[ $tag ] );

        // Make sure at least one priority has a filter callback
        if ( $has ) {
            $exists = false;
            foreach ( $filter[ $tag ] as $callbacks ) {
                if ( ! empty( $callbacks ) ) {
                    $exists = true;
                    break;
                }
            }

            if ( ! $exists ) {
                $has = false;
            }
        }

        if ( false === $function_to_check || false === $has ) {
            return $has;
        }

        if ( ! $idx = _wp_filter_build_unique_id( $tag, $function_to_check, false ) ) {
            return false;
        }

        foreach ( (array)array_keys( $filter[ $tag ] ) as $priority ) {
            if ( isset( $filter[ $tag ][ $priority ][ $idx ] ) ) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Removes a function from a specified action hook.
     *
     * This function removes a function attached to a specified action hook. This
     * method can be used to remove default functions attached to a specific filter
     * hook and possibly replace them with a substitute.
     *
     * @param string   $tag                The action hook to which the function to be removed is hooked.
     * @param callback $function_to_remove The name of the function which should be removed.
     * @param int      $priority           Optional. The priority of the function. Default 10.
     *
     * @return bool Whether the function is removed.
     */
    public function remove_action( $tag, $function_to_remove, $priority = 10 )
    {
        return self::remove_filter( $tag, $function_to_remove, $priority );
    }

    /**
     * Removes a function from a specified filter hook.
     *
     * This function removes a function attached to a specified filter hook. This
     * method can be used to remove default functions attached to a specific filter
     * hook and possibly replace them with a substitute.
     *
     * To remove a hook, the $function_to_remove and $priority arguments must match
     * when the hook was added. This goes for both filters and actions. No warning
     * will be given on removal failure.
     *
     * @param string   $tag                The filter hook to which the function to be removed is hooked.
     * @param callback $function_to_remove The name of the function which should be removed.
     * @param int      $priority           Optional. The priority of the function. Default 10.
     *
     * @return bool    Whether the function existed before it was removed.
     */
    public function remove_filter( $tag, $function_to_remove, $priority = 10 )
    {
        $function_to_remove = self::_filter_build_unique_id( $tag, $function_to_remove, $priority );

        $r = isset( $$this->filter[ $tag ][ $priority ][ $function_to_remove ] );

        if ( true === $r ) {
            unset( $this->filter[ $tag ][ $priority ][ $function_to_remove ] );
            if ( empty( $this->filter[ $tag ][ $priority ] ) ) {
                unset( $this->filter[ $tag ][ $priority ] );
            }
            if ( empty( $this->filter[ $tag ] ) ) {
                $this->filter[ $tag ] = array();
            }
            unset( $this->merged_filters[ $tag ] );
        }

        return $r;
    }

    /**
     * Remove all of the hooks from an action.
     *
     * @param string   $tag      The action to remove hooks from.
     * @param int|bool $priority The priority number to remove them from. Default false.
     *
     * @return true True when finished.
     */
    public function remove_all_actions( $tag, $priority = false )
    {
        return self::remove_all_filters( $tag, $priority );
    }

    /**
     * Remove all of the hooks from a filter.
     *
     * @param string   $tag      The filter to remove hooks from.
     * @param int|bool $priority Optional. The priority number to remove. Default false.
     *
     * @return true True when finished.
     */
    public function remove_all_filters( $tag, $priority = false )
    {
        if ( isset( $this->filter[ $tag ] ) ) {
            if ( false === $priority ) {
                $this->filter[ $tag ] = array();
            } elseif ( isset( $this->filter[ $tag ][ $priority ] ) ) {
                $this->filter[ $tag ][ $priority ] = array();
            }
        }

        unset( $this->merged_filters[ $tag ] );

        return true;
    }

}
