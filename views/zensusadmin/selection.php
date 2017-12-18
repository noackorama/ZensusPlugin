<?php
echo $this->render_partial('zensusadmin/_widgets.php');
?>
<form action="" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <caption>
                <?= htmlReady(sprintf(_('Veranstaltungen im %s'), $semester->name)) ?>
            <span class="actions">
                <?= sprintf('%u %s', count($data), count($data) > 1 ? _('Veranstaltungen') : _('Veranstaltung')) ?>
            </span>
        </caption>
        <thead>
        <tr>
        <th>...</th>
        <th><?=_("Institut")?></th>
        <th><?=_("Nr.")?></th>
        <th><?=_("Titel")?></th>
        <th><?=_("Lehrende")?></th>
        <th><?=_("Form der Teilnahme")?></th>
        <th><?=_("Sprache")?></th>
        <th><?=_("Art des Fragebogens")?></th>
        <th><?=_("Wdh")?></th>
        <th><?=_("Erhebungszeitraum")?></th>
        </tr>
        </thead>
        <tbody>
        <? foreach ($data as $r) : ?>
        <tr>
        <td><input type = "checkbox"></td>
            <td><?=htmlready($r['institute'])?></td>
            <td><?=htmlready($r['nr'])?></td>
            <td><?=htmlready($r['name'])?></td>
            <td><?=htmlready(join(',', $r['dozenten']))?></td>
            <td><?=_("Form der Teilnahme")?></td>
            <td><?=htmlready($r['sprache'])?></td>
            <td><?=_("Art des Fragebogens")?></td>
            <td><?=htmlready($r['wdhl'])?></td>
            <td><?=$r['eval_start_time'] . '-' . $r['eval_end_time']?></td>
        </tr>
        <? endforeach ?>
        </tbody>
    </table>



