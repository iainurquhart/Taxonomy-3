# Taxonomy 3.0

### Overview

Taxonomy 3.0 is a rewrite of 2.0, and includes breaking changes as some of the primary tags behave differently to previous versions.

These docs are really loose and are by no means complete, but should be sufficient to get you going with 3.0

#### Main changes

1. taxonomy:nav tag does not automatically add the <li> elements any more, giving you FULL control over the output markup.

2. taxonomy:breadcrumbs is now a tag pair, also giving you FULL control of the output markup.

3. exp:taxonomy:entries is a new tag which largely removes the need for embeds when outputting child teasers on landing pages.

### Code Examples

Set your current tree so you don't have to keep adding the tree_id parameter, as before. Optionally (and preferably implicitly declare the current node's entry_id)

	{exp:taxonomy:set_node tree_id="1" entry_id="{entry_id}"}

Updated :nav tag with {children} var, and <li> wrappers

	{exp:taxonomy:nav 
		auto_expand="yes"
		display_root="no"
	}
		<li><a href="{node_url}">{node_title}</a>{children}</li>
	{/exp:taxonomy:nav}

--

Updated :breadcrumbs tag using {here}

	{exp:taxonomy:breadcrumbs tree_id="1"}
		{if here}
			{node_title}
		{if:else}
			<a href="{node_url}">{node_title}</a> &rarr; 
		{/if}
	{/exp:taxonomy:breadcrumbs}

:breadcrumbs also has {not_here}, {node_count}, {node_total_count} and {node_level} variables.

New :entries tag can be dropped right inside an outer channel entries tag. Works by extending channel:entires, and prefixes all variables with 'tx:', including variable pairs. Note the parent_entry_id parameter.

	{exp:taxonomy:entries parent_entry_id="{entry_id}" dynamic="no"}
		{if tx:count == 1}<div class="line"></div>{/if}
		<article class="post medium">
			<h2><a href="{exp:taxonomy:node_url entry_id="{tx:entry_id}"}">{tx:title}</a></h2>
			</header>
			<p>{tx:page_introduction}</p>
		</article>
	{/exp:taxonomy:entries}



### Installing/Updating
Please review the following instructions: 
http://iain.co.nz/software/docs/installation-updating-instructions

### Documentation
Docs can be found at http://iain.co.nz/software/docs/taxonomy

### Support and Feature Requests
Please post on the @devot_ee forums:
http://devot-ee.com/add-ons/support/taxonomy/viewforum/403/

Copyright (c) 2011 Iain Urquhart
http://iain.co.nz