<div id="Sidebar">
	<div class="sidebarTop"></div>
	<% include Sidebar_Cart %>
	<div class="sidebarBottom"></div>
</div>
<div id="ProductGroup" class="mainSection">
	<h1 id="PageTitle">$Title</h1>
	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>
	<% if Tags %><div id="TagList"><% include EcommerceProductTagList %></div><% end_if %>
<% if Products %>
	<div id="Products" class="category">
		<div class="resultsBar">
			<% if SortLinks %><span class="sortOptions"><% _t('ProductGroup.SORTBY','Sort by') %>
			<% loop SortLinks %><a href="$Link" class="sortlink $Current">$Name</a> <% end_loop %></span>
			<% end_if %>
		</div>
		<ul class="productList"><% loop Products %><% include ProductGroupItem %><% end_loop %></ul>
		<div class="clear"><!-- --></div>
	</div>
<% include ProductGroupPagination %>
<% end_if %>
	<% if Form %><div id="FormHolder">$Form</div><% end_if %>
	<% if PageComments %><div id="PageCommentsHolder">$PageComments</div><% end_if %>

</div>




