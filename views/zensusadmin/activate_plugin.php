<h1><?=sprintf(_('Zensus Plugin f�r %s Veranstaltungen ein- oder ausschalten'), count($courses))?></h1>
<form class="default" action="<?=$controller->link_for('/activate_plugin')?>" method="post">
<?= CSRFProtection::tokenTag() ?>
    <?=addHiddenFields('selected_courses', $courses)?>
    <fieldset>
    <label><input type="radio" name="plugin_active" value="1" checked><?=_('Einschalten')?> </label>
    <label><input type="radio" name="plugin_active" value="0"><?=_('Ausschalten')?></label>
    </fieldset>
    <div data-dialog-button>
        <?=Studip\Button::createAccept(_('�bernehmen'), 'save')?>
    </div>
</form>
