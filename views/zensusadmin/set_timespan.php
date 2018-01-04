<h1><?=sprintf(_('Start- und Endzeiten für %s Veranstaltungen setzen'), count($courses))?></h1>
<form class="default" action="<?=$controller->link_for('/set_timespan')?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <label>
            <?= _('Start') ?>
            <input class="has-date-picker size-s" type="text" name="startdate" value="">
        </label>
        <label >
            <?= _('Ende') ?>
            <input class="has-date-picker size-s" type="text" name="enddate" value="">
        </label>
    </fieldset>
    <div data-dialog-button>
        <?=Studip\Button::createAccept(_('Übernehmen'), 'save')?>
    </div>
</form>