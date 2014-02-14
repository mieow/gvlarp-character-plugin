<form action="/" method="get">
    <fieldset>
        <input type="text" name="s" id="search" class="searchbox" value="<?php the_search_query(); ?>" />
        <input type="image" alt="Search" class="searchsubmit" src="<?php echo get_template_directory_uri(); ?>/images/search.png" />
    </fieldset>
</form>