<?php

echo $this->render_partial('zensusadmin/_widgets.php');
?>
<form action="<?=$controller->link_for()?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default default sortable-table" data-sortlist="[[0, 0]]">
        <caption>
                <?= htmlReady(sprintf(_('Veranstaltungen im %s'), $semester->name)) ?>
            <span style="font-size: small"><?=_('(Grün hinterlegte Veranstaltungen können nicht mehr verändert werden)')?></span>
            <span class="actions">
                <?= sprintf('%u %s', count($data), count($data) > 1 ? _('Veranstaltungen') : _('Veranstaltung')) ?>
            </span>
        </caption>
        <thead>
        <tr>
        <th> <input type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=selected_courses]"></th>
        <th data-sort="text"><?=_("Institut")?></th>
        <th data-sort="text"><?=_("Nr.")?></th>
        <th data-sort="text"><?=_("Titel")?></th>
        <th data-sort="text"><?=_("Lehrende")?></th>
        <th data-sort="text"><?=_("TN")?></th>
        <th data-sort="text"><?=_("Status Evaluation")?></th>
        <th data-sort="htmldata"><?=_("Form der Teilnahme")?></th>
        <th data-sort="text"><?=_("Sprache")?></th>
        <th data-sort="htmldata"><?=_("Art des Fragebogens")?></th>
        <th data-sort="htmldata"><?=_("Wdhl")?></th>
        <th data-sort="htmldata"><?=_("Start")?></th>
        <th data-sort="htmldata"><?=_("Ende")?></th>
        </tr>
        </thead>
        <tbody>
        <? foreach ($data as $course_id => $r) : ?>
        <tr <?=$r['evaluation'] ? 'style="background-color: lightgreen"' : ''?>>
        <td><input type="checkbox" name="selected_courses[<?=htmlready($course_id)?>]" value="1"></td>
            <td><?=htmlready($r['institute'])?></td>
            <td><?=htmlready($r['nr'])?></td>
            <td><?=htmlready($r['name'])?><a href="<?=URLHelper::getLink('dispatch.php/course/details', ['sem_id' => $course_id])?>" data-dialog><?=Icon::create('info-circle', 'inactive', ['title' => _('Veranstaltungsdetails')])?></a></td>
            <td><?=htmlready(join(',', $r['dozenten']))?></td>
            <td><?=htmlready($r['teilnehmer_anzahl_aktuell'])?></td>
            <td><?=$r['evaluation'] ? _('Ja') : _('Nein')?></td>
            <td data-sort-value="<?=htmlready($r['fb'])?>">
                <?=DataFieldEntry::createDataFieldEntry($datafield_fb, $course_id, $r['fb'])->getDisplayValue("datafields[$course_id]");?>

            </td>
            <td>
                <?=DataFieldEntry::createDataFieldEntry($datafield_sprache, $course_id, $r['sprache'])->getDisplayValue("datafields[$course_id]");?>
            </td>
            <td data-sort-value="<?=htmlready($r['fb'])?>">
                <?=DataFieldEntry::createDataFieldEntry($datafield_form, $course_id, $r['form'])->getDisplayValue("datafields[$course_id]");?>
            </td>
            <td data-sort-value="<?=htmlready($r['wdhl'])?>">
            <?= ($r['wdhl'] ? _("Ja") : _("Nein")) ?>
            </td>
            <td data-sort-value="<?=htmlready(strtotime($r['eval_start_time']))?>"><?=$r['eval_start_time']?></td>
            <td data-sort-value="<?=htmlready(strtotime($r['eval_end_time']))?>"><?=$r['eval_end_time']?></td>
        </tr>
        <? endforeach ?>
        </tbody>
        <tfoot>
        <tr>
            <td style="padding-left:5px" colspan="13"><label><input type="checkbox" name="all" value="1" data-proxyfor=":checkbox[name^=selected_courses]"> <?=_("Alle auswählen")?></label></td>
        </tr>
        </tfoot>
    </table>
    <div>
        <?=Studip\Button::create(_("Nachricht an Lehrende"), 'mail', ['onClick' => 'STUDIP.Dialog.fromElement(this,{});return false;']);?>
        <?=Studip\Button::create(_("Teilnahmeform"), 'set_form', ['onClick' => 'STUDIP.Dialog.fromElement(this,{\'size\' : \'auto\'});return false;']);?>
        <?=Studip\Button::create(_("Fragebogen"), 'set_frage', ['onClick' => 'STUDIP.Dialog.fromElement(this,{\'size\' : \'auto\'});return false;']);?>
        <?=Studip\Button::create(_("Wiederholung"), 'set_wdhl', ['onClick' => 'STUDIP.Dialog.fromElement(this,{\'size\' : \'auto\'});return false;']);?>
        <?=Studip\Button::create(_("Sprache"), 'set_sprache', ['onClick' => 'STUDIP.Dialog.fromElement(this,{\'size\' : \'auto\'});return false;']);?>
        <?=Studip\Button::create(_("Start/Endzeit setzen"), 'set_timespan', ['onClick' => 'STUDIP.Dialog.fromElement(this,{\'size\' : \'auto\'});return false;']);?>
    </div>
</form>


