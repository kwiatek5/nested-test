<table>
		<tr>
			<th>id</th>
			<th>title</th>
			<th>lft</th>
			<th>rgt</th>
		</tr>
	<?php foreach ($nodes as $k => $v): ?>
		<tr>
			<td>
				<?php echo $v['id']; ?>
			</td>
			<td>
				<?php echo str_repeat('&nbsp;', 4 * $v['depth']) . $v['title']; ?>
			</td>
			<td style="text-align: right;">
				<?php echo $v['lft']; ?>
			</td>
			<td style="text-align: right;">
				<?php echo $v['rgt']; ?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>