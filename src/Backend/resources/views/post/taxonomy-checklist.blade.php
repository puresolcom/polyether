{!! Taxonomy::UITerms([
    'orderby'           => 'id',
    'order'             => 'ASC',
    'with_post_counts'  => false,
    'hide_empty'        => false,
    'echo'              => false,
    'hierarchical'      => $taxObj->hierarchical,
    'name'              => "taxonomy[$taxName][]",
    'selected'          => array_pluck(Taxonomy::getObjectTerms($postId, $taxName), 'id'),
    'value_field'       => 'term_id',
    'taxonomy'          => $taxName,
    ])
!!}