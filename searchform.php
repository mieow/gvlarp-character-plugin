<?php
	$default = array('colours' => 'red');
	$options  = get_option('nocturnus_options', $default);
	$theme    = $options['colours'];
?>
<form action="/" method="get">
    <fieldset>
        <input type="text" name="s" id="search" class="searchbox" value="<?php the_search_query(); ?>" size=20 />
        <input type="image" alt="Search" class="searchsubmit" src="<?php echo get_template_directory_uri(); ?>/images/search<?php echo $theme; ?>.png" />
    </fieldset>
</form>