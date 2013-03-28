<script type="text/javascript" charset="utf-8">
	// <![CDATA[
	$(document).ready(function() {
		$(".taxonomy-tree-delete a").click(function(e) { 
			var answer = confirm("Are you sure you want to delete?")
			if (!answer){
				e.preventDefault();
			}
		});
	});
	// ]]>
</script>

<div class="taxonomy-tree-list">
<?php

	if(isset($trees))
	{
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			array('data' => lang('tx_tree_id'), 'style' => 'width: 25px; text-align: center;'),
			lang('tx_tree_label'),
			lang('tx_tree_short_name'),
			lang('tx_tree_preferences'),
			lang('tx_delete')
		);
		foreach($trees as $tree)
		{
			$this->table->add_row(
				array('data' => $tree['id'], 'class' => 'taxonomy-id'),
				array('data' => $tree['edit_nodes_link'], 'class' => 'taxonomy-tree-label'),
				array('data' => $tree['name'], 'class' => 'taxonomy-tree-name'),
				array('data' => $tree['edit_tree_link'], 'class' => 'taxonomy-tree-manage'),
				array('data' => $tree['delete_tree_link'], 'class' => 'taxonomy-tree-delete')
			);
		}
		echo $this->table->generate();
	}
	else
	{
		echo lang('no_trees_assigned');
	}

?>
</div>