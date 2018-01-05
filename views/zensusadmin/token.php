<p>
    <?=_("Für den Import der Veranstaltungsdaten in das Zensus System müssen sie dort ein Authentifizierungstoken hinterlegen.");?>
    <br>
    <?=_("Hier können Sie ein Token für Ihre aktuelle Nutzerkennung generieren.");?>
</p>
<div>
    <span style="font-weight:bold; padding-right: 10px;"><?=_("Nutzerkennung:")?></span>
    <span><?=$GLOBALS['user']->username . ' (' . $GLOBALS['user']->perms  . ')'?></span>
</div>

<div>
    <span style="font-weight:bold; padding-right:10px;"><?=_("Token:")?></strong></span>
    <span><?=htmlReady(UserConfig::get($GLOBALS['user']->id)->UNIZENSUSPLUGIN_AUTH_TOKEN)?></span>
</div>

<form method="post" action="<?=$controller->link_for()?>" data-dialog>
<button class="button" type="submit" name="generate_token">
    <?=_("neues Token erzeugen")?>
</button>
</form>
