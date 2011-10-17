<% if Icon %><span class="icon">$Icon.SetSize(32, 32)</span><% end_if %>
<% if Link %>
	<a href="$Link" class="title"><strong>$Title</strong></a>
<% else %>
	<strong class="title">$Title</strong>
<% end_if %>
<% if Explanation %><span class="explanation">$Explanation</span><% end_if %>
<% if ExplanationPage %><a  href="$ExplanationPage.Link"  title="$Title.ATT - $Explanation.ATT">more ...</a><% end_if %>
