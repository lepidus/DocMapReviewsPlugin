<link rel="stylesheet" type="text/css" href="/plugins/generic/docMapReviews/styles/docMapReviews.css">
<script type="text/javascript" src="/plugins/generic/docMapReviews/js/docMapReviews.js"></script>
<div class="item prereview">
    <h2>{translate key="plugins.generic.docMapReviews.reviews"}</h2>
    <div class="review-groups-container">
        {if !empty($reviewGroups)}
            {foreach item=group from=$reviewGroups}
                <div class="review-group">
                    <div class='review-group-header'>
                        <img class="review-group-logo" src="{$group.logo}" alt="{$group.name}"/>
                        <div>{$group.name}</div>
                    </div>

                    {foreach item=review from=$group.reviews}
                        <div class='review-container'>
                            <div class='review-info-container'>
                                <div class="review-published-date">{$review.published}</div>
                                <div class="review-reviewer-name">{$review.name}</div>
                            </div>
                            <div class="review-link-container">
                                <a class="review-link truncate" href="{$review.link}" target="_blank">{$review.link}</a>
                            </div>

                            <div id='btn-{$review.id}' class='more see-hide-review-button' onclick='more("{$review.id}")'>
                                <a>{translate key="plugins.generic.docMapReviews.displayReviews"}</a>
                                <a>►</a>
                            </div>
                            <div id='less-{$review.id}' class='less see-hide-review-button' onclick='less("{$review.id}")'>
                                <a>{translate key="plugins.generic.docMapReviews.hideReviews"}</a>
                                <a>▼</a>
                            </div>

                            <div id='div-{$review.id}' class='contents'>{$review.webContent}</div>
                        </div>
                    {/foreach}
                </div>
            {/foreach}
            {else}
            <p>{translate key="plugins.generic.docMapReviews.noReviewsAvailable"}</p>
        {/if}
    </div>
</div>
