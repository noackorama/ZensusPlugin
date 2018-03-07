<?php
class UniZensusNagScreen extends StudIPPlugin implements SystemPlugin
{
    function __construct()
    {
        parent::__construct();
        if (match_route('dispatch.php/my_courses')) {
            $nag_screen_shown = UserConfig::get($GLOBALS['user']->id)->UNIZENSUS_NAG_SCREEN_LAST_SHOWN;
            $user_id = $GLOBALS['user']->id;

            if (!$GLOBALS['perm']->have_perm('dozent')
                && $nag_screen_shown < @Semester::findCurrent()->beginn
            ) {
                $content_box = addcslashes(formatReady(Config::get()->UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT), "'\n\r");
                $titel_box = addcslashes(Config::get()->UNIZENSUSPLUGIN_DISPLAYNAME, "'\n\r");
                $img_url = $this->getPluginUrl($this) . '/images/danger.png';
                $script = <<<EOT
jQuery('document').ready(function(){
    if(jQuery('img[title="Den Fragebogen aufrufen und an der Evaluation teilnehmen"]').length + jQuery('image[alt="Den Fragebogen aufrufen und an der Evaluation teilnehmen"]').length > 0 && jQuery('#UniZensusNagScreenDialogbox').length == 0) {
    STUDIP.UniZensusNagScreen = {};
    STUDIP.UniZensusNagScreen.dialog = jQuery('<div id="UniZensusNagScreenDialogbox"><img style="padding: 5px" src="$img_url" align="right"><span>' + '$content_box' + '</span></div>').dialog({
                       show: '',
                       hide: 'scale',
                       title: '$titel_box',
                       draggable: false,
                       modal: true,
                       width: Math.min(600, jQuery(window).width() - 64),
                       height: 'auto',
                       maxHeight: jQuery(window).height(),
                       close: function(){jQuery(this).remove();}
                     });
    }
});
EOT;
                UserConfig::get($GLOBALS['user']->id)->store('UNIZENSUS_NAG_SCREEN_LAST_SHOWN', time());
                PageLayout::addHeadElement('script', array('type' => 'text/javascript'), $script);
            }

            if ($GLOBALS['perm']->get_perm() == 'dozent'
                && $nag_screen_shown < @Semester::findCurrent()->beginn
            ) {
                $content_box = addcslashes(formatReady(Config::get()->UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT_DOZENT), "'\n\r");
                $titel_box = addcslashes(Config::get()->UNIZENSUSPLUGIN_DISPLAYNAME, "'\n\r");
                $img_url = $this->getPluginUrl($this) . '/images/danger.png';
                $script = <<<EOT
jQuery('document').ready(function(){
    if(jQuery('img[title*="Die Evaluation läuft"]').length + jQuery('image[alt*="Die Evaluation läuft"]').length > 0 && jQuery('#UniZensusNagScreenDialogbox').length == 0) {
    STUDIP.UniZensusNagScreen = {};
    STUDIP.UniZensusNagScreen.dialog = jQuery('<div id="UniZensusNagScreenDialogbox"><img style="padding: 5px" src="$img_url" align="right"><span>' + '$content_box' + '</span></div>').dialog({
                       show: '',
                       hide: 'scale',
                       title: '$titel_box',
                       draggable: false,
                       modal: true,
                       width: Math.min(600, jQuery(window).width() - 64),
                       height: 'auto',
                       maxHeight: jQuery(window).height(),
                       close: function(){jQuery(this).remove();}
                     });
    }
});
EOT;
                UserConfig::get($GLOBALS['user']->id)->store('UNIZENSUS_NAG_SCREEN_LAST_SHOWN', time());
                PageLayout::addHeadElement('script', array('type' => 'text/javascript'), $script);
            }

        }
    }

}

