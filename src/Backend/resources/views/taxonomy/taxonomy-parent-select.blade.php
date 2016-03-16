{!! Taxonomy::UITerms( [
'show_options_all'  => 'Select Parent', 'show_option_none' => 'None',
'option_none_value' => '0', 'echo' => false, 'hierarchical' => true,
'spacer'            => '&nbsp;&nbsp;&nbsp;&nbsp;', 'exclude' => $exclude,
'name' => "parent",
'class'             => 'form-control', 'value_field' => 'term_id',
'taxonomy'          => $taxName, 'type' => 'dropdown'
] ) !!}