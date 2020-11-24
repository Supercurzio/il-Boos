
<?php

function bbg_change_profile_tab_order() {
global $bp;

$bp->bp_member_options_nav[‘profile’][‘position’] = 10;
$bp->bp_nav[‘activity’][‘position’] = 20;
$bp->bp_nav[‘friends’][‘position’] = 30;
$bp->bp_nav[‘groups’][‘position’] = false;
$bp->bp_nav[‘blogs’][‘position’] = false;
$bp->bp_member_options_nav[‘notifications’][‘position’] = false;
$bp->bp_nav[‘messages’][‘position’] = false;
$bp->bp_nav[‘settings’][‘position’] = 40;
}
add_action( ‘bp_member_options_nav’, ‘bbg_change_profile_tab_order’, 999 );

?>