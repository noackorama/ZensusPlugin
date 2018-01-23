<h1><?=sprintf(_('Art des Fragebogens f�r %s Veranstaltungen w�hlen'), count($courses))?></h1>
<form class="default" action="<?=$controller->link_for('/set_frage')?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <?=addHiddenFields('selected_courses', $courses)?>
    <fieldset>
        <label>
            <?=DataFieldEntry::createDataFieldEntry($datafield_form, 'set_frage', '')->getHTML("set_frage")?>
        </label>
    </fieldset>
    <div data-dialog-button>
        <?=Studip\Button::createAccept(_('�bernehmen'), 'save')?>
    </div>
</form>