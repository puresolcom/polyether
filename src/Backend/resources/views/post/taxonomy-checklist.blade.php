{!! Taxonomy::UITerms([
    'orderby'           => 'id',
    'order'             => 'ASC',
    'with_post_counts'  => false,
    'hide_empty'        => false,
    'echo'              => false,
    'hierarchical'      => $taxObj->hierarchical,
    'name'              => "taxonomy[$taxName][]",
    'class'             => 'icheck',
    'selected'          => (isset($postId) && abs((int)$postId !== 0)) ? array_pluck(Taxonomy::getObjectTerms($postId, $taxName), 'id') : 0,
    'value_field'       => 'term_id',
    'taxonomy'          => $taxName,
    ])
!!}