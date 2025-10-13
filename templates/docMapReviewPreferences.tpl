<link rel="stylesheet" type="text/css" href="/plugins/generic/docMapReviews/styles/docMapReviews.css">

{load_header context="backend"}

<div id="docMapReviews">
    <div id="historyHeader">
        <h2>{translate key="plugins.generic.docMapReviews.displayName"}</h2>
    </div>
    {if $isPublished}
        <p>{translate key="plugins.generic.docMapReviews.prePubDescriptionPartOne"}</p>
        <p>{translate key="plugins.generic.docMapReviews.prePubDescriptionPartTwo"}</p>
        <p>{translate key="plugins.generic.docMapReviews.postPubDescription"}</p>

        <div id="displayReviewsPreferences">
            {capture assign=reviewOfferPrefsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.docMapReviews.controllers.grid.DocMapReviewsGridHandler" op="fetchGrid" submissionId=$submissionId escape=false}{/capture}
            {load_url_in_div id="docMapsReviewPrefsGridContainer"|uniqid url=$reviewOfferPrefsGridUrl}
        </div>

    {else}
        <p>{translate key="plugins.generic.docMapReviews.prePubDescriptionPartOne"}</p>
        <p>{translate key="plugins.generic.docMapReviews.prePubDescriptionPartTwo"}</p>
        <p>{translate key="plugins.generic.docMapReviews.prePubDescriptionPartThree"}</p>

        <div id="displayReviewsPreferences">
            {capture assign=reviewOfferPrefsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.docMapReviews.controllers.grid.DocMapReviewsGridHandler" op="fetchGrid" submissionId=$submissionId escape=false}{/capture}
            {load_url_in_div id="docMapsReviewPrefsGridContainer"|uniqid url=$reviewOfferPrefsGridUrl}
        </div>
    {/if}
</div>