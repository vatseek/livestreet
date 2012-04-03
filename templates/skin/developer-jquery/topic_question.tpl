{include file='topic_part_header.tpl'}


<div id="topic_question_area_{$oTopic->getId()}" class="poll">
	{if !$oTopic->getUserQuestionIsVote()}
		<ul class="poll-vote">
			{foreach from=$oTopic->getQuestionAnswers() key=key item=aAnswer}
				<li><label><input type="radio" id="topic_answer_{$oTopic->getId()}_{$key}" name="topic_answer_{$oTopic->getId()}" value="{$key}" onchange="$('#topic_answer_{$oTopic->getId()}_value').val($(this).val());" /> {$aAnswer.text|escape:'html'}</label></li>
			{/foreach}
		</ul>

		<button onclick="ls.poll.vote({$oTopic->getId()},$('#topic_answer_{$oTopic->getId()}_value').val());" class="button button-primary">{$aLang.topic_question_vote}</button>
		<button onclick="ls.poll.vote({$oTopic->getId()},-1)" class="button">{$aLang.topic_question_abstain}</button>
		
		<input type="hidden" id="topic_answer_{$oTopic->getId()}_value" value="-1" />

		<p class="poll-total">{$aLang.topic_question_vote_result}: {$oTopic->getQuestionCountVote()} | {$aLang.topic_question_abstain_result}: {$oTopic->getQuestionCountVoteAbstain()}</p>
	{else}
		{include file='question_result.tpl'}
	{/if}
</div>


<div class="topic-content text">
	{hook run='topic_content_begin' topic=$oTopic bTopicList=$bTopicList}
	
	{if $bTopicList}
		{$oTopic->getTextShort()}
		{if $oTopic->getTextShort()!=$oTopic->getText()}
			<a href="{$oTopic->getUrl()}#cut" title="{$aLang.topic_read_more}">
				{if $oTopic->getCutText()}
					{$oTopic->getCutText()}
				{else}
					{$aLang.topic_read_more}
				{/if}
			</a>
		{/if}
	{else}
		{$oTopic->getText()}
	{/if}
	
	{hook run='topic_content_end' topic=$oTopic bTopicList=$bTopicList}
</div> 



{include file='topic_part_footer.tpl'}