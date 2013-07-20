# Taxonomy 3.0

### Overview

Taxonomy 3.0 is a rewrite of 2.0, and includes breaking changes as some of the primary tags behave differently to previous versions. You will *have* to update some template code if you are updating from 2.x. If you want to revert to 2.0, you'll need to reinstate the taxonomy database tables as 3.0 changes the db schema also.

These docs are really loose and are by no means complete, but should be sufficient to get you going with 3.0. Nearly all tag parameters and variables exist using the [2.x docs](http://iain.co.nz/taxonomy/)

#### Main changes

1. taxonomy:nav tag does not automatically add the &lt;li&gt; elements any more, giving you FULL control over the output markup.

2. taxonomy:breadcrumbs is now a tag pair, also giving you FULL control of the output markup.

3. exp:taxonomy:entries is a new tag which largely removes the need for embeds when outputting child teasers on landing pages.

4. No more 'Use Page Uri' options in the backend; if a node entry has a uri, Taxonomy is going to assume you want to use it.

5. The UI has been redesigned and the db schema now uses nested set and adjacency model for heirarchy.

4. :get_sibling_ids is now :sibling_entry_ids

5. :next_node and :prev_node are a work in progress. If you're updating and make use of these tags, best wait till they are done.

6. Introduction of a 'taxonomy_updated' extension hook which fires when a tree is updated (allows you to clear caches for example)

7. The taxonomy nav tag can also act as a single tag, meaning a simple unordered list is generated with the full set of existing parameters avaialble to you.

### Code Examples

Set your current tree so you don't have to keep adding the tree_id parameter, as before. Optionally (and preferably) implicitly declare the current node's entry_id:

	{exp:taxonomy:set_node tree_id="1" entry_id="{entry_id}"}

--

Updated :nav tag with {children} var, and &lt;li&gt; wrappers

	{exp:taxonomy:nav 
		auto_expand="yes"
		display_root="no"
	}
		<li><a href="{node_url}">{node_title}</a>{children}</li>
	{/exp:taxonomy:nav}

Updated linear :nav tag using own output of wrapper ul and li active, active_parent classes

	{exp:taxonomy:nav 
	    auto_expand="yes"
	    tree_id="1"
	    style="linear"
	}
	    {if node_level_count == 1}<ul>{/if}
	    <li class="node_{node_id}{if node_active} active{/if}{if node_active_parent} active_parent{/if}">
	        <a href="{node_url}">{node_title}</a>{children}</li>
	    {if node_level_count == node_level_total_count}</ul>{/if}
	{/exp:taxonomy:nav}

Updated :nav tag as a single tag, not a tag pair. Note it's best to supply an additional operator on the :nav incase you are using the :nav pair elsehwere in your template

	{exp:taxonomy:nav:single tree_id="2"}

--

Updated :breadcrumbs tag using {here}

	{exp:taxonomy:breadcrumbs}
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

--

Full example using Stash

	{exp:channel:entries limit="1" ... }

		<!-- set the node and tree -->
		{exp:taxonomy:set_node tree_id="1" entry_id="{entry_id}"}

		{exp:stash:set_value name="page_title" value="{title}"}

		{exp:stash:set name="breadcrumbs"}
			{exp:taxonomy:breadcrumbs}
				{if here}
					{node_title}
				{if:else}
					<a href="{node_url}">{node_title}</a> &rarr; 
				{/if}
			{/exp:taxonomy:breadcrumbs}
		{/exp:stash:set}


		{exp:stash:set name="subnav"}
			{exp:taxonomy:nav 
				ul_css_class="categories"
				auto_expand="yes"
				active_branch_start_level="1"}
				<li{if node_active} class="active"{/if}>
					<a href="{node_url}">{node_title}</a>
					{children}
				</li>
			{/exp:taxonomy:nav}
		{/exp:stash:set}


		{exp:stash:set name="main_content"}
			{if page_introduction}
			<h3 class="kicker">{page_introduction}</h3>
			{/if}

			{main_content}

			<!-- show the child entries -->
			{exp:taxonomy:entries parent_entry_id="{entry_id}" dynamic="no"}
				{if tx:count == 1}
					<h3>In this section</h3>
					<div class="line"></div>
				{/if}
				<article class="post medium">
					<h2><a href="{exp:taxonomy:node_url entry_id="{tx:entry_id}"}">{tx:title}</a></h2>
					</header>
					<p>{tx:page_introduction}</p>
				</article>
			{/exp:taxonomy:entries}
		{/exp:stash:set}


		{exp:stash:set name="related_information"}
			<!-- show the sibling entries -->
			{exp:taxonomy:entries fixed_order="{exp:taxonomy:sibling_entry_ids}" parse="inward" dynamic="no"}
				{if tx:count == 1}
					<h3>Related Content:</h3>
				{/if}
				<h1>{tx:title}</h1>
				<p>{tx:page_introduction}</p>
				<a href="{exp:taxonomy:node_url entry_id="{tx:entry_id}"}">{tx:title}</a>
			{/exp:taxonomy:entries}
		{/exp:stash:set}

		{exp:stash:set name="footer_nav"}

			{exp:taxonomy:next_node tree_id="1" entry_id="{entry_id}"}
			<p>Next node is <strong>{next_label}</strong></p>
			{/exp:taxonomy:next_node}

			{exp:taxonomy:prev_node tree_id="1" entry_id="{entry_id}"}
			<p>Previous node is <strong>{prev_label}</strong></p>
			{/exp:taxonomy:prev_node}

		{/exp:stash:set}

	{/exp:channel:entries}

--

### 'taxonomy_updated' Extension hook

The taxonomy_updated hook gets fired whenever:

* a node is updated
* a tree is re-ordered, 
* a node is deleted, 
* a branch is deleted, 
* an entry with a taxonomy fieldtype is saved

	if (ee()->extensions->active_hook('taxonomy_updated'))
	{
	    ee()->extensions->call('taxonomy_updated', $this->tree_id, $update_type, $data);
	}

Update types are:

* 'update_node', $data gives you details of the node that got updated
* 'reorder_nodes', $data gives you an array of the updated tree structure
* 'delete_branch' and 'delete_node', $data gives you an array of the node that got deleted. Both are essentially the same except delete branch just flags that the node was a parent and there would have been children deleted also.
* 'fieldtype_save', $data gives you an indication of what was updated when the Taxonomy fieldtype's save method was called.

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