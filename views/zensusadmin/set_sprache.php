<h1><?=sprintf(_('Sprache für %s Veranstaltungen wählen'), count($courses))?></h1>
<form class="default" action="<?=$controller->link_for('/set_sprache')?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <?=addHiddenFields('selected_courses', $courses)?>
    <fieldset>
        <label>
            <?=DataFieldEntry::createDataFieldEntry($datafield_sprache, 'set_sprache', '')->getHTML("set_sprache")?>
        </label>
    </fieldset>
    <div data-dialog-button>
        <?=Studip\Button::createAccept(_('Übernehmen'), 'save')?>
    </div>
</form>