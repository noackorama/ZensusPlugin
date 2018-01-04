<?php
$widget = new OptionsWidget();
$widget->setTitle(_("Filter"));
$widget->addCheckbox(
    _("Evaluation in Zensus aktiviert"),
    $filter['zensus_activated'],
    $controller->link_for('/status', ['toggle_zensus_active' => 1])
);
$widget->addCheckbox(
    _("Evaluation in Zensus deaktiviert"),
    $filter['zensus_deactivated'],
    $controller->link_for('/status', ['toggle_zensus_nonactive' => 1])
);
$widget->addCheckbox(
    _("Plugin aktiv"),
    $filter['plugin_activated'],
    $controller->link_for('/status', ['toggle_plugin_active' => 1])
);
Sidebar::Get()->addWidget($widget);
echo $this->render_partial('zensusadmin/_widgets.php');
?>

<form action="<?=$controller->link_for()?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default default sortable-table" data-sortlist="[[0, 0]]">
        <caption>
            <?= htmlReady(sprintf(_('Veranstaltungen im %s'), $semester->name)) ?>
            <span class="actions">
                <?= sprintf('%u %s', count($data), count($data) > 1 ? _('Veranstaltungen') : _('Veranstaltung')) ?>
            </span>
        </caption>
        <thead>
        <tr>
            <th> <input type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=selected_courses]"></th>
            <th data-sort="text"><?=_("Nr.")?></th>
            <th data-sort="text"><?=_("Titel")?></th>
            <th data-sort="text"><?=_("Lehrende")?></th>
            <th data-sort="text"><?=_("TN")?></th>
            <th data-sort="text"><?=_("Zensus TN")?></th>
            <th data-sort="text"><?=_("Zensus Status")?></th>
            <th data-sort="text"><?=_("Plugin aktiv")?></th>
            <th data-sort="text"><?=_("Ergebnis")?></th>
            <th data-sort="text"><?=_("Studierende")?></th>
            <th data-sort="htmldata"><?=_("Start")?></th>
            <th data-sort="htmldata"><?=_("Ende")?></th>
        </tr>
        </thead>
        <tbody>
        <? foreach ($data as $course_id => $r) : ?>
        <tr>
            <td><input type="checkbox" name="selected_courses[<?=htmlready($course_id)?>]" value="1"></td>
            <td><?=htmlready($r['nr'])?></td>
            <td><?=htmlready($r['name'])?></td>
            <td><?=htmlready(join(',', $r['dozenten']))?></td>
            <td><?=htmlready($r['teilnehmer_anzahl_aktuell'])?></td>
            <td><?=htmlready($r['zensus_numvotes'])?></td>
            <td><?=htmlready($r['zensus_status'])?></td>
            <td><?=htmlready($r['plugin_activated'])?></td>
            <td><?=htmlready($r['eval_public'])?></td>
            <td><?=htmlready($r['eval_public_stud'])?></td>
            <td data-sort-table="<?=htmlready(strtotime($r['eval_start_time']))?>"><?=$r['eval_start_time']?></td>
            <td data-sort-table="<?=htmlready(strtotime($r['eval_end_time']))?>"><?=$r['eval_end_time']?></td>
        </tr>
        <? endforeach;?>
        </tbody>
    </table>
    <div>
        <?=Studip\Button::create(_("Nachricht an Lehrende"), 'mail', ['onClick' => 'STUDIP.Dialog.fromElement(this,{});return false;']);?>
        <?=Studip\Button::create(_("Plugin aktivieren/deaktivieren"), 'activate_plugin', ['onClick' => 'STUDIP.Dialog.fromElement(this,{\'size\' : \'auto\'});return false;']);?>
        <?=Studip\Button::create(_("Start/Endzeit setzen"), 'set_timespan', ['onClick' => 'STUDIP.Dialog.fromElement(this,{\'size\' : \'auto\'});return false;']);?>
    </div>
</form>


