<% if EcommerceProductTags %>
<div id="EcommerceProductTags">
	<h3><% _t("ATTRIBUTES", "Attributes") %></h3>
	<ul class="tagList">
		<% control EcommerceProductTags %>
		<li class="$OddEven $FirstLast">
			<% include EcommerceProductTagItem %>
		</li><% end_control %>
	</ul>
</div>
<% end_if %>
