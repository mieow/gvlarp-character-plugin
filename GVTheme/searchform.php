<form action="/" method="get">
    <fieldset>
        <input type="text" name="s" id="search" class="searchbox" value="<?php the_search_query(); ?>" />
        <input type="image" alt="Search" class="searchsubmit" src="<?php bloginfo( 'template_url' ); ?>/images/search.png" />
    </fieldset>
</form>