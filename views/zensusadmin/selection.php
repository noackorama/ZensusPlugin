<?php

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
        <th data-sort="text"><?=_("Institut")?></th>
        <th data-sort="text"><?=_("Nr.")?></th>
        <th data-sort="text"><?=_("Titel")?></th>
        <th data-sort="text"><?=_("Lehrende")?></th>
        <th data-sort="text"><?=_("TN")?></th>
        <th data-sort="htmldata"><?=_("Form der Teilnahme")?></th>
        <th data-sort="text"><?=_("Sprache")?></th>
        <th data-sort="htmldata"><?=_("Art des Fragebogens")?></th>
        <th data-sort="text"><?=_("Wdh")?></th>
        <th data-sort="htmldata"><?=_("Erhebungszeitraum")?></th>
        </tr>
        </thead>
        <tbody>
        <? foreach ($data as $course_id => $r) : ?>
        <tr>
        <td><input type="checkbox" name="selected_courses[<?=htmlready($course_id)?>]" value="1"></td>
            <td><?=htmlready($r['institute'])?></td>
            <td><?=htmlready($r['nr'])?></td>
            <td><?=htmlready($r['name'])?></td>
            <td><?=htmlready(join(',', $r['dozenten']))?></td>
            <td><?=htmlready($r['teilnehmer_anzahl_aktuell'])?></td>
            <td data-sort-table="<?=htmlready($r['fb'])?>">
                <?=DataFieldEntry::createDataFieldEntry($datafield_fb, $course_id, $r['fb'])->getHTML("datafields[$course_id]");?>

            </td>
            <td><?=htmlready($r['sprache'])?></td>
            <td data-sort-table="<?=htmlready($r['fb'])?>">
                <?=DataFieldEntry::createDataFieldEntry($datafield_form, $course_id, $r['form'])->getHTML("datafields[$course_id]");?>
            </td>
            <td data-sort-table="<?=htmlready($r['wdhl'])?>">
            <?=DataFieldEntry::createDataFieldEntry($datafield_wdhl, $course_id, $r['wdhl'])->getHTML("datafields[$course_id]");?>
            </td>
            <td data-sort-table="<?=htmlready(strtotime($r['eval_start_time']))?>"><?=$r['eval_start_time'] . '-' . $r['eval_end_time']?></td>
        </tr>
        <? endforeach ?>
        </tbody>
    </table>
    <div>
        <?=Studip\Button::create(_("Nachricht an Lehrende"), 'mail', ['onClick' => 'STUDIP.Dialog.fromElement(this,{});return false;']);?>
        <?=Studip\Button::createAccept(_("Speichern"), 'save');?>
    </div>
</form>


