<h1><?=sprintf(_('Form der Teilnahme für %s Veranstaltungen wählen'), count($courses))?></h1>
<form class="default" action="<?=$controller->link_for('/set_form')?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <?=addHiddenFields('selected_courses', $courses)?>
    <fieldset>
        <label>
            <?=DataFieldEntry::createDataFieldEntry($datafield_fb, 'set_form', '')->getHTML("set_form")?>
        </label>
    </fieldset>
    <div data-dialog-button>
        <?=Studip\Button::createAccept(_('Übernehmen'), 'save')?>
    </div>
</form>