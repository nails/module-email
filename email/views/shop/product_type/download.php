<p>
	Thanks for ordering this downloadable content from <?=APP_NAME?>. Please find below your download links. The order reference for these files is <strong><?=$order->ref?></strong>.
</p>
<?php

	$_plural = count( $urls ) > 1 ? TRUE : FALSE;

	echo '<p class="heads-up">';

	if ( $_plural ) :

		echo '<strong>Please note: </strong> these links are only valid for ' . ( $expires/60/60 ) . ' hours after which the links will stop working';

	else :

		echo '<strong>Please note: </strong> this link is only valid for ' . ( $expires/60/60 ) . ' hours after which it will stop working';

	endif;
	
	if ( $sent_to->id ) :
	
		echo '; however you can always ' . anchor( 'auth/login', 'log in to your ' . APP_NAME . ' account' ) . ' to access your downloads any time.';
	
	else :
	
		echo '. If you register an account with us using this email (<strong>' . $sent_to->email . '</strong>) then we\'ll automatically associate your previous orders so you can always access your files.';
		
	endif;
	
	
	echo '<hr />';
	
	echo '<ul>';
	
	foreach ( $urls AS $url ) :
	
		echo '<li>';
		echo '&rsaquo; ' . anchor( $url->url, $url->title );
		echo '</li>';
	
	endforeach;
	
	echo '</ul>';
	
?>
<hr />
<p>
	Thanks again for shopping with <?=APP_NAME?>, your business is very much appreciated!
</p>