<script type="text/javascript">
$(document).ready(function () {

	$('#taxonomy-subnav input').change(function () {
	    
		var currentId = $(this).attr('id');
		var targetId = '#rel-'+currentId;
		var slideDuration = 500;

		// console.log('Targetting: ' + targetId);

		if ( $(this).is(":checked") )
		{
			$('li'+targetId).stop(true, true).fadeIn({ duration: slideDuration, queue: false }).css('display', 'none').slideDown(slideDuration); 
		}
		else
		{
			$('li'+targetId).stop(true, true).fadeOut({ duration: slideDuration, queue: false }).slideUp(slideDuration);
		}

	 });

});
</script>


<div id="taxonomy-node-builder">

	<div id="taxonomy-node-label-holder">
			<div class="taxonomy-inset">
				<label for="node-label">Label / Title:</label>
				<input type="text" id="node-label" />
			</div>
	</div>

	<div id="taxonomy-builder">
		<ul>
			<li id="select-parent">
				<div class="taxonomy-inset">
					<label for="node-entry">Select Parent:</label>
					<select id="node-entry">
						<option>--</option>
						<option>[Pages] Home Page</option>
						<option>[Pages] Another Page</option>
					</select>
				</div>
			</li>
			<li class="taxonomy-hidden" id="rel-check-1">
				<div class="taxonomy-inset">
					<label for="node-template">Select Template:</label>
					<select id="node-template">
						<option>--</option>
						<option>/about</option>
						<option>/about/team</option>
					</select>
				</div>
			</li>
			<li class="taxonomy-hidden" id="rel-check-2">
				<div class="taxonomy-inset">
					<label for="node-entry">Select Entry:</label>
					<select id="node-entry">
						<option>--</option>
						<option>[Pages] Home Page</option>
						<option>[Pages] Another Page</option>
					</select>
				</div>
			</li>
			<li class="taxonomy-hidden" id="rel-check-3">
				<div class="taxonomy-inset">
					<label for="node-url">Custom Link:</label>
					<input type="text" id="node-url" value="http://" />
				</div>
			</li>
		</ul>
	</div>
	<div id="taxonomy-subnav">
		<ul>
			<li><h3>Item Type:</h3></li>
			<li class="first">
				<label for="check-1"><input type="checkbox" id="check-1" /> Template</label>
			</li>
			<li>
				<label for="check-2"><input type="checkbox" id="check-2" /> Entry / Page</label>
			</li>
			<li>
				<label for="check-3"><input type="checkbox" id="check-3" /> Custom Link</label>
			</li>
		</ul>

		<input type="submit" name="submit" value="Submit" class="taxonomy-submit submit"  />


	</div>
</div>