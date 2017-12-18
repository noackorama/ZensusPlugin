<?php





$list = new SelectWidget(_('Einrichtung'), URLHelper::getURL("?"), 'institut_id');
if ($GLOBALS['perm']->have_perm('root')) {
    $list->addElement(new SelectElement('all', _('Alle'), $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT === 'all'), 'select-all');
}
foreach (Institute::getMyInstitutes() as $institut) {
    $list->addElement(
        new SelectElement(
            $institut['Institut_id'],
            (!$institut['is_fak'] ? "  " : "") . $institut['Name'],
            $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT == $institut['Institut_id']
        ),
        'select-' . $institut['Name']
    );
}
Sidebar::Get()->addWidget($list);


$semesters = array_reverse(Semester::getAll());
$list = new SelectWidget(_('Semester'), URLHelper::getURL("?"), 'semester_id');
foreach ($semesters as $semester) {
    $list->addElement(new SelectElement($semester->id, $semester->name, $semester->id === $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE), 'sem_select-' . $semester->id);
}
Sidebar::Get()->addWidget($list);

$export = new ExportWidget();
$export->addLink(_("Datenexport"), URLHelper::getLink("?export"), Icon::create('file-excel', 'clickable'));
Sidebar::Get()->addWidget($export);