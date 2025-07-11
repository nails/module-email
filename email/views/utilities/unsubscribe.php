<?php

/**
 * @var string $logo
 * @var string $title
 * @var string $body
 * @var string $btnText
 * @var string $btnUrl
 */

?>
<div class="nails-email unsubscribe center-screen">
    <?php

    if ($logo) {
        echo '<div class="logo">';
        echo img([
            'src' => $logo,
        ]);
        echo '</div>';
    }

    ?>
    <div class="panel">
        <div class="panel__header">
            <h1 class="panel__title text-center">
                <?=$title?>
            </h1>
        </div>
        <div class="panel__body">
            <p class="text-center">
                <?=$body?>
            </p>
            <p>
                <?=anchor($btnUrl, $btnText, 'class="btn btn--block"')?>
            </p>
        </div>
    </div>
</div>
