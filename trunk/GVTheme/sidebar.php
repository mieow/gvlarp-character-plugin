	<div id="side" class="widget-area">
		<div id="searchform" class="search-area">
			<h2 class="widgettitle">Search</h2>
			<?php get_search_form( ); ?> 
		</div>
		<ul>
			<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('gvsidebar') ) : ?>
			<?php endif; ?>
		</ul>
	</div>