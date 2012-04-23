<ul class="latest-list">
	{foreach from=$aComments item=oComment name="cmt"}
		{assign var="oUser" value=$oComment->getUser()}
		{assign var="oTopic" value=$oComment->getTarget()}
		{assign var="oBlog" value=$oTopic->getBlog()}
		
		<li class="js-title-comment" title="{$oComment->getText()|strip_tags|truncate:100:'...'}">
			<p>
				<a href="{$oUser->getUserWebPath()}" class="author">{$oUser->getLogin()}</a>
				<time>{date_format date=$oComment->getDate() hours_back="12" minutes_back="60" now="60" day="day H:i" format="H:i"}</time>
			</p>
			<a href="{if $oConfig->GetValue('module.comment.nested_per_page')}{router page='comments'}{else}{$oTopic->getUrl()}#comment{/if}{$oComment->getId()}" class="stream-topic">{$oTopic->getTitle()|escape:'html'}</a>
			<i>{$oTopic->getCountComment()}</i>
		</li>
	{/foreach}
</ul>


<footer>
	<a href="{router page='comments'}">{$aLang.block_stream_comments_all}</a> | <a href="{router page='rss'}allcomments/">RSS</a>
</footer>