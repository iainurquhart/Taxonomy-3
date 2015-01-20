$(document).ready(function() {
	// click to fetch entry title to node title
	$('.taxonomy_fetch_title').click(function(e) 
	{	
			e.preventDefault();
			var titleVal = $('input[name="title"]').val();
			var fieldTable = $(this).closest('table');
			fieldTable.find('.taxonomy_label').val(titleVal);								
	});
});