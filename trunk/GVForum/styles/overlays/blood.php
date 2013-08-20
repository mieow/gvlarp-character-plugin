<?php

# --------------------------------------------------------------------------------------
#
#	Simple:Press Template Color Attribute File
#	Theme		:	default
#	Color		:	Kindred Blood
#	Author		:	Simple:Press
#
# --------------------------------------------------------------------------------------

# ------------------------------------------------------------------
# The overall SP forum container
# ------------------------------------------------------------------
$mainBackGroundBase		= '#000000';
$mainBackGroundFrom		= '#000000';
$mainBackGroundTo		= '#000000';
#$mainBackGroundBorder	= '1px solid #555555';
$mainBackGroundBorder	= 'none';
$mainBackGroundColor	= '#cccccc';
$mainBackGroundHover	= '1px solid #555555';
$mainBackGroundGradient	= "-moz-linear-gradient(100% 100% 90deg, $mainBackGroundTo, $mainBackGroundFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($mainBackGroundFrom), to($mainBackGroundTo)); background-color: $mainBackGroundBase;";
$mainBackGroundSolid	= '#000000';
$mainBackGroundImage	= 'url("images/image.gif")';
$mainBackGround			= $mainBackGroundSolid; # pick background from: $mainBackGroundSolid, $mainBackGroundImage or $mainBackGroundGradient
$mainFontSize			= '100%';
$mainLineHeight			= '1.2em';

# ------------------------------------------------------------------
# font families and Weights
# ------------------------------------------------------------------
$mainFontFamily			= 'inherit';
$altFontFamily			= 'inherit';
$headingFontFamily		= 'inherit';
$buttonFontFamily		= 'inherit';
$controlFontFamily		= 'inherit';
$toolTipFontFamily		= 'inherit';
$dialogFontFamily		= 'inherit';
$mainFontWeight			= 'normal';
$headingFontWeight		= 'bold';
$legendFontWeight       = 'bold';

# ------------------------------------------------------------------
# Plain section - the main building block of non-forum areas
# ------------------------------------------------------------------
$plainSectionBase		= 'inherit';
$plainSectionFrom		= 'inherit';
$plainSectionTo			= 'inherit';
$plainSectionBorder		= 'none';
$plainSectionColor		= 'inherit';
$plainSectionHover		= 'none';
$plainSectionGradient	= "-moz-linear-gradient(100% 100% 90deg, $plainSectionTo, $plainSectionFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($plainSectionFrom), to($plainSectionTo)); background-color: $plainSectionBase;";
$plainSectionSolid		= 'inherit';
$plainSectionImage		= 'url("images/image.gif")';
$plainSectionBackGround	= $plainSectionSolid; # pick background from: $plainSectionSolid, $plainSectionImage or $plainSectionGradient

# ------------------------------------------------------------------
# The over container of forum lists
# ------------------------------------------------------------------
$listSectionBase		= '#000000';
$listSectionFrom		= '#000000';
$listSectionTo			= '#000000';
$listSectionBorder		= 'none';
$listSectionColor		= '#cccccc';
$listSectionHover		= 'none';
$listSectionGradient	= "-moz-linear-gradient(100% 100% 90deg, $listSectionTo, $listSectionFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($listSectionFrom), to($listSectionTo)); background-color: $listSectionBase;";
$listSectionSolid		= '#000000';
$listSectionImage		= 'url("images/image.gif")';
$listSectionBackGround	= $listSectionSolid; # pick background from: $listSectionSolid, $listSectionImage or $listSectionGradient

# ------------------------------------------------------------------
# Header sections within lists
# ------------------------------------------------------------------
$itemHeaderBase			= '#560606';
$itemHeaderFrom			= '#560606';
$itemHeaderTo			= '#770506';
$itemHeaderBorder		= '1px solid #BB0506';
$itemHeaderColor		= '#cccccc';
$itemHeaderHover		= '1px solid #dddddd';
$itemHeaderGradient		= "-moz-linear-gradient(100% 100% 90deg, $itemHeaderTo, $itemHeaderFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($itemHeaderFrom), to($itemHeaderTo)); background-color: $itemHeaderBase;";
$itemHeaderSolid		= '#770506';
$itemHeaderImage		= 'url("images/image.gif")';
$itemHeaderBackGround	= $itemHeaderGradient; # pick background from: $itemHeaderSolid, $itemHeaderImage or $itemHeaderGradient
$headerMessageColor     ='#FFFFFF';

# ------------------------------------------------------------------
# Item sections within list
# ------------------------------------------------------------------
$itemListBase					= '#080808';
$itemListFrom					= '#080808';
$itemListTo						= '#080808';
$itemListBorder					= '1px solid #333333';
$itemListColor					= '#cccccc';
$itemListGradient				= "-moz-linear-gradient(100% 100% 90deg, $itemListTo, $itemListFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($itemListFrom), to($itemListTo)); background-color: $itemListBase;";
$itemListSolid					= '#080808';
$itemListImage					= 'url("images/image.gif")';
$itemListBackGround				= $itemListSolid; # pick background from: $itemListSolid, $itemListImage or $itemListGradient
$itemListColorHover				= '#cccccc';
$itemListBorderHover			= '1px solid #333333';
$itemListGradientHover			= "-moz-linear-gradient(100% 100% 90deg, $itemListTo, $itemListFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($itemListFrom), to($itemListTo)); background-color: $itemListBase;";
$itemListSolidHover				= '#080808';
$itemListImageHover				= 'url("images/image.gif")';
$itemListBackGroundHover		= $itemListSolidHover; # pick background from: $itemListSolidHover, $itemListImageHover or $itemListGradientHover

$itemListBaseOdd				= '#080808';
$itemListFromOdd				= '#080808';
$itemListToOdd					= '#080808';
$itemListBorderOdd				= '1px solid #333333';
$itemListColorOdd				= '#cccccc';
$itemListGradientOdd			= "-moz-linear-gradient(100% 100% 90deg, $itemListToOdd, $itemListFromOdd); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($itemListFromOdd), to($itemListToOdd)); background-color: $itemListBaseOdd;";
$itemListSolidOdd				= '#080808';
$itemListImageOdd				= 'url("images/image.gif")';
$itemListBackGroundOdd			= $itemListSolidOdd; # pick background from: $itemListSolidOdd, $itemListImageOdd or $itemListGradientOdd
$itemListColorOddHover			= '#cccccc';
$itemListBorderOddHover			= '1px solid #333333';
$itemListGradientOddHover		= "-moz-linear-gradient(100% 100% 90deg, $itemListTo, $itemListFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($itemListFrom), to($itemListTo)); background-color: $itemListBase;";
$itemListSolidOddHover			= '#080808';
$itemListImageOddHover			= 'url("images/image.gif")';
$itemListBackGroundOddHover		= $itemListSolidOddHover; # pick background from: $itemListSolidOddHover, $itemListImageOddHover or $itemListGradientOddHover

$itemListBaseEven				= '#111111';
$itemListFromEven				= '#111111';
$itemListToEven					= '#111111';
$itemListBorderEven				= '1px solid #333333';
$itemListColorEven				= '#cccccc';
$itemListGradientEven			= "-moz-linear-gradient(100% 100% 90deg, $itemListToEven, $itemListFromEven); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($itemListFromEven), to($itemListToEven)); background-color: $itemListBaseEven;";
$itemListSolidEven				= '#111111';
$itemListImageEven				= 'url("images/image.gif")';
$itemListBackGroundEven			= $itemListSolidEven; # pick background from: $itemListSolidEven, $itemListImageEven or $itemListGradientEven
$itemListColorEvenHover			= '#cccccc';
$itemListBorderEvenHover		= '1px solid #333333';
$itemListGradientEvenHover		= "-moz-linear-gradient(100% 100% 90deg, $itemListTo, $itemListFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($itemListFrom), to($itemListTo)); background-color: $itemListBase;";
$itemListSolidEvenHover			= '#111111';
$itemListImageEvenHover			= 'url("images/image.gif")';
$itemListBackGroundEvenHover	= $itemListSolidEvenHover; # pick background from: $itemListSolidEvenHover, $itemListImageEvenHover or $itemListGradientEvenHover

# ------------------------------------------------------------------
# Success / Fail
# ------------------------------------------------------------------
$successBackGround	= '#000000';
$successBorder		= '1px solid #888888';
$successColor		= '#cccccc';

$failBackGround		= '#660000';
$failBorder			= '1px solid #888888';
$failColor			= '#cccccc';

$noticeBackGround	= '#cccccc';
$noticeBorder		= '1px solid #555555';
$noticeColor		= '#000000';

# ------------------------------------------------------------------
# Alternate Backgrounds
# ------------------------------------------------------------------
$alt1SectionBase		= '#565656';
$alt1SectionFrom		= '#565656';
$alt1SectionTo			= '#242424';
$alt1SectionBorder		= '1px solid #555555';
$alt1SectionColor		= '#cccccc';
$alt1SectionHover		= '1px solid #dd2222';
$alt1SectionGradient	= "-moz-linear-gradient(100% 100% 90deg, $alt1SectionTo, $alt1SectionFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($alt1SectionFrom), to($alt1SectionTo)); background-color: $alt1SectionBase;";
$alt1SectionSolid		= '#565656';
$alt1SectionImage		= 'url("images/image.gif")';
$alt1SectionBackGround	= $alt1SectionGradient; # pick background from: $alt1SectionSolid, $alt1SectionImage or $alt1SectionGradient

$alt2SectionBase		= '#565656';
$alt2SectionFrom		= '#565656';
$alt2SectionTo			= '#242424';
$alt2SectionBorder		= '1px solid #555555';
$alt2SectionColor		= '#cccccc';
$alt2SectionHover		= '#505050';
$alt2SectionGradient	= "-moz-linear-gradient(100% 100% 90deg, $alt2SectionTo, $alt2SectionFrom); background: -webkit-gradient(linear, 0% 0%, 0% 100%, from($alt2SectionFrom), to($alt2SectionTo)); background-color: $alt2SectionBase;";
$alt2SectionSolid		= '#080808';
$alt2SectionImage		= 'url("images/image.gif")';
$alt2SectionBackGround	= $alt2SectionSolid; # pick background from: $alt2SectionSolid, $alt2SectionImage or $alt2SectionGradient

# ------------------------------------------------------------------
# Alternate color variations
# ------------------------------------------------------------------
$alt1BackGround		= '#505050';
$alt1Border			= '1px solid #111111';
$alt1Color			= '#cccccc';

$alt2BackGround		= '#505050';
$alt2Border			= 'none';
$alt2Color			= '#555555';

$alt3BackGround		= '#000000';
$alt3Border			= '1px solid #111111';
$alt3Color			= '#444444';

$alt4BackGround		= '#222222';
$alt4Border			= '1px solid #555555';
$alt4Color			= '#cccccc';

$alt5BackGround		= '#222222';
$alt5Border			= '1px solid #555555';
$alt5Color			= '#cccccc';

$alt6BackGround		= '#00ff00';
$alt6Border			= 'none';
$alt6Color			= '#555555';

# ------------------------------------------------------------------
# form control element backgrounds
# ------------------------------------------------------------------
$controlBackGround		= '#111111';
$controlBorder			= '1px solid #cccccc';
$controlColor			= '#cccccc';
$controlHeight			= '25px';
$linkButtonHeight		= '21px';
$controlLineHeight		= '1.6em';

$controlBackGroundHover	= '#232323';
$controlBorderHover		= '1px solid #dd2222';
$controlColorHover		= '#ff0000';

# ------------------------------------------------------------------
# profile tab/menu element backgrounds
# ------------------------------------------------------------------
$profileBackGround		= $alt1SectionGradient;
$profileBorder			= '1px solid #555555';
$profileColor			= '#cccccc';
$profileHeight			= '23px';

$profileBackGroundHover	= '#242424';
$profileBorderHover		= '1px solid #555555';
$profileColorHover		= '#cccccc';

$profileBackGroundCur	= '#CCCCCC';
$profileBorderCur		= '1px solid #555555';
$profileColorCur		= '#000000';

$profileBackGroundAlt	= $alt1SectionGradient;

$profileTabsRadius      ='-webkit-gradientborder-radius: 5px 5px 0 0;border-radius: 5px 5px 0 0;';

# ------------------------------------------------------------------
# Post Content
# ------------------------------------------------------------------
$postBackGround			= '#080808';
$postBorder				= '1px solid #555555';
$postColor				= '#cccccc';
$postBackGroundOdd		= '#080808';
$postBorderOdd			= '1px solid #555555';
$postColorOdd			= '#cccccc';
$postBackGroundEven		= '#080808';
$postBorderEven			= '1px solid #555555';
$postColorEven			= '#cccccc';

$userBackGround			= '#000000';
$userBorder				= '1px solid #555555';
$userColor				= $itemListColor;
$userBackGroundOdd		= '#000000';
$userBorderOdd			= '1px solid #555555';
$userColorOdd			= $itemListColor;
$userBackGroundEven		= '#000000';
$userBorderEven			= '1px solid #555555';
$userColorEven			= $itemListColor;

$postPadding			= '0';
$postMargin				= '2px 2px 5px 2px';
$postBackRadius			= '-webkit-gradientborder-radius: 0px 5px 5px 0px; border-radius: 0px 5px 5px 0px;';

# ------------------------------------------------------------------
# Standard Links
# ------------------------------------------------------------------
$linkColor				= '#787878';
$linkHover				= '#ff0000';
$linkDecoration			= 'none';

$alt1LinkColor			= '#AAAAAA';
$alt1LinkHover			= '#ff0000';
$alt1LinkDecoration		= 'none';

$alt2LinkColor			= '#ff1717';
$alt2LinkHover			= '#ff0000';
$alt2LinkDecoration		= 'none';

$alt3LinkColor			= '#787878';
$alt3LinkHover			= '#ff0000';
$alt3LinkDecoration		= 'none';

$alt4LinkColor			= '#cccccc';
$alt4LinkHover			= '1px solid #ff0000';
$alt4LinkDecoration		= 'none';

$alt5LinkColor			= '#ff1717';
$alt5LinkHover			= '#ff0000';
$alt5LinkDecoration		= 'none';

$alt6LinkColor			= '#AAAAAA';
$alt6LinkHover			= '#ff0000';
$alt6LinkDecoration		= 'underline';

# ------------------------------------------------------------------
# Standard Radii
# ------------------------------------------------------------------
$smallRadius	= '-webkit-gradientborder-radius: 5px; border-radius: 5px;';
$largeRadius	= '-webkit-gradientborder-radius: 9px; border-radius: 9px;';

# ------------------------------------------------------------------
# Misc Font Sizes
# ------------------------------------------------------------------
$PostContentFontSize           ='90%';
$spMainContainerSmall          ='90%';
$spListTopicRowName            ='70%';
$spListForumRowName            ='85%';
$spListPostLink_spListLabel    ='85%';
$UserInfo                      ='80%';
$spPostUserSignature           ='90%';
$spEven_spPostUserPosts        ='90%';
$spOdd_spPostUserPosts         ='90%';
$spPostContent_h1              ='1.6em';
$spPostContent_h2              ='1.5em';
$spPostContent_h3              ='1.4em';
$spPostContent_h4              ='1.3em';
$spPostContent_h5              ='1.2em';
$spPostContent_h6              ='1.1em';
$spSpoiler                     ='0.85em';
$divsfcode                     ='1em';
$inputsfcodeselect             ='10px';
$spPostForm                    ='85%';
$spEditorTitle                 ='1.1em';
$spLabelBordered               ='100%';
$spLabelBorderedButton         ='80%';
$spLabelSmall                  ='80%';
$spButtonAsLabel               ='80%';
$spProfileShowHeader           ='1.4em';
$spProfileShowHeaderEdit       ='0.6em';
$spBreadCrumbs                 ='0.85em';
$spHeaderName                  ='100%';
$spHeaderDescription           ='80%';
$spInHeaderLabel               ='80%';
$spInHeaderSubForums           ='80%';
$aspRowName                    ='95%';
$MemberListSectionspRowName    ='90%';
$spRowDescription              ='80%';
$spInRowForumPageLink          ='85%';
$spInRowLabel                  ='80%';
$spInRowRankDateNumber         ='90%';
$spListSectionInRowRankDateNumber ='80%';
$spInRowLastPostLink           ='80%';
$spOddspInRowSubForums         ='80%';
$spOddhoverspInRowSubForums    ='80%';
$spEvenspInRowSubForums        ='80%';
$spEvenhoverspInRowSubForums   ='80%';
$spAck                         ='85%';
$spUnreadPostsInfo             ='0.9em';
$aspPageLinks                  ='0.8em';
$spForumTimeZone               ='0.8em';
$spFooterStats                 ='0.8em';
$spLoginSearchAdvancedForms    ='90%';
$spSearchForm                  ='0.8em';
$pspSearchDetails              ='0.8em';
$spControl                     ='100%';
$spSubmit                      ='80%';
$labellist                     ='1em';
$pvtip                         ='12px';
$spMessageSuccessFailure       ='90%';
$ulspProfileTabs               ='0.8em';
$spProfileHeader               ='1.2em';
$lispProfileMenuItem           ='0.9em';
$spProfileFormPane             ='0.9em';
$aspToolsButton                ='80%';
$FontspQuickLinks              ='80%';

# ------------------------------------------------------------------
# Some Component Widths
# ------------------------------------------------------------------

$quickLinksSelectWidth		= '230px';
$quickLinksListWidth		= '300px';

# ------------------------------------------------------------------
# Images
# ------------------------------------------------------------------
$OnOff                 ='transparent url("images/onoff.png") 0 -3px no-repeat';
$ImageClose            ='url("images/close.gif")';
$ImageResize           ='url("images/resize.gif")';
$Imagedd_arrow         ='url("images/dd_arrow.gif") no-repeat 0 0';
$Imagesp_ImageOverlay  ='#666666 url("images/sp_ImageOverlay.png") 50% 50% repeat';

# ------------------------------------------------------------------
#Quicklinks Child Colors on dropdown
# ------------------------------------------------------------------

$pQuickLinksTopicspPostNew     ='#488ccc !important';
$pQuickLinksTopicspPostMod     ='#f26565 !important';
$spQuickLinksTopichover        ='#FFFFFF';
$spQuickLinksTopicColor        ='#C0C0C0';

?>