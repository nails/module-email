<?php

/**
 * @var string $logo
 * @var string $title
 * @var string $body
 * @var string $btnText
 * @var string $btnUrl
 */

?>
<div class="nails-auth login u-center-screen">
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
        <h1 class="panel__header text-center">
            <?=$title?>
        </h1>
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
