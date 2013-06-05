	</div> <!-- close main -->

	<footer id="gvfooter">
		<div id="gvcredits">
			<?php $options = get_option('gv_options'); ?>
			<table>
			<tbody>
			<tr>
			<td class="gvcred-left"><?php echo $options['copyright']; ?></td>
			<td class="gvcred-center"></td>
			<td class="gvcred-right"><?php echo $options['credits']; ?></td>
			</tr>
			</tbody>
			</table>
		</div>
	</footer>

</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>