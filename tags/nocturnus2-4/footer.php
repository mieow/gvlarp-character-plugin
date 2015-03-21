	</div> <!-- close main -->

	<footer id="nocturnus_footer">
		<div id="nocturnus_credits">
			<?php $options = get_option('nocturnus_options'); ?>
			<table>
			<tbody>
			<tr>
			<td class="cred-left"><?php echo $options['copyright']; ?></td>
			<td class="cred-center"></td>
			<td class="cred-right"><?php echo $options['credits']; ?></td>
			</tr>
			</tbody>
			</table>
		</div>
	</footer>

</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>