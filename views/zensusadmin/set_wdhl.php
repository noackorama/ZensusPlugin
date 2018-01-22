<h1><?=sprintf(_('Wiederholung setzen f�r %s'), $institute->name)?></h1>
<p><?=_("Wiederholung der Abfrage bei mehr als 1. Lehrenden. Diese Einstellung wird immer auf alle Veranstaltungen der ausgew�hlten Einrichtung angewendet.")?></p>
<form class="default" action="<?=$controller->link_for('/set_wdhl')?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <label><input type="radio" name="wdhl" value="1" checked><?=_('Einschalten')?> </label>
        <label><input type="radio" name="wdhl" value="0"><?=_('Ausschalten')?></label>
    </fieldset>
    <div data-dialog-button>
        <?=Studip\Button::createAccept(_('�bernehmen'), 'save')?>
    </div>
</form>