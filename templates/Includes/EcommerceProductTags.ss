<% if EcommerceProductTags %>
<div id="EcommerceProductTags">
	<h3><% _t("ATTRIBUTES", "Attributes") %></h3>
	<ul>
		<% control EcommerceProductTags %>
		<li class="$OddEven $FirstLast">
			<a <% if ExplanationPage %> href="$ExplanationPage.Link" <% end_if %> title="$Title.ATT - $Explanation.ATT">
				$Icon.SetSize(32, 32)
				<em>$Title</em>
				<span>$Explanation</span>
			</a>
		</li><% end_control %>
	</ul>
</div>
<% end_if %>
