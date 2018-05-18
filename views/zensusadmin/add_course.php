<p>
    <?=_("Hier können Sie Veranstaltungen zur Auswahl hinzufügen.");?>
</p>

<form role="search" action="<?=$controller->link_for() ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="search_sem_qs_choose" value="title_lecturer_number">
    <input type="hidden" name="search_sem_sem" value="<?=$semester_number?>">
    <input type="hidden" name="search_sem_category" value="1">

    <?php
    echo QuickSearch::get("search_course", new SeminarSearch())
        ->setAttributes(array(
            "style" => "width: 70%"
        ))
        ->noSelectbox()
        ->render();
    echo Icon::create('add')->asInput([
        'title' => _('Hinzufügen'),
        'type'  => 'image',
        'class' => 'quicksearchbutton',
        'name'  => 'add_course',
    ])
    ?>
</form>