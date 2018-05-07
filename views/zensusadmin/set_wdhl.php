<h1><?=sprintf(_('Wiederholung für %s Veranstaltungen wählen'), count($courses))?></h1>
<p><?=_("Wiederholung der Abfrage bei mehr als 1. Lehrenden.")?></p>
<form class="default" action="<?=$controller->link_for('/set_wdhl')?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <?=addHiddenFields('selected_courses', $courses)?>
    <fieldset>
        <label><input type="radio" name="set_wdhl" value="1" checked><?=_('Einschalten')?> </label>
        <label><input type="radio" name="set_wdhl" value="0"><?=_('Ausschalten')?></label>
    </fieldset>
    <div data-dialog-button>
        <?=Studip\Button::createAccept(_('Übernehmen'), 'save')?>
    </div>
</form>