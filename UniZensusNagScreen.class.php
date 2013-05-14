<?php
class UniZensusNagScreen extends StudIPPlugin implements SystemPlugin
{
    function __construct()
    {
        parent::__construct();
        if (!$GLOBALS['perm']->have_perm('dozent') && in_array(basename($_SERVER['PHP_SELF']), array('meine_seminare.php'))
            && (time() - $_SESSION['nag_screen_shown']) > (24*60*60)
        ) {
            $user_id = $GLOBALS['user']->id;

            $content_box = addcslashes(formatReady(Config::get()->UNIZENSUSPLUGIN_NAG_SCREEN_CONTENT),"'\n\r");
            $titel_box = addcslashes(Config::get()->UNIZENSUSPLUGIN_DISPLAYNAME,"'\n\r");
            $img_url = $this->getPluginUrl($this). '/images/danger.png';
            $script = <<<EOT
jQuery('document').ready(function(){
    if(jQuery('img[title="Den Fragebogen aufrufen und an der Evaluation teilnehmen"]').length > 0 && jQuery('#UniZensusNagScreenDialogbox').length == 0) {
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

               	$_SESSION['nag_screen_shown'] = time();
                PageLayout::addHeadElement('script', array('type'=>'text/javascript'), $script);
            }
        }

}

