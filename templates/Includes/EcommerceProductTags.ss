<% if EcommerceProductTags %>
<div id="EcommerceProductTags">
	<h3><% _t("TAGS", "Tags") %></h3>
	<ul class="tagList">
		<% with/loop EcommerceProductTags %>
		<li class="$OddEven $FirstLast">
			<% include EcommerceProductTagItem %>
		</li><% end_with/loop %>
	</ul>
</div>
<% end_if %>
